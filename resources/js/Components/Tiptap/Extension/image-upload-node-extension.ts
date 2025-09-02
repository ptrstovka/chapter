import { randomString } from "@/Utils";
import { mergeAttributes, Node, VueNodeViewRenderer } from '@tiptap/vue-3'
import { ref } from "vue";
import TiptapImageUploadNode from "../TiptapImageUploadNode.vue";

export type UploadFunction = (
  file: File,
  onProgress?: (event: { progress: number }) => void,
  abortSignal?: AbortSignal
) => Promise<string>

export interface FileItem {
  id: string
  file: File
  progress: number
  status: "uploading" | "success" | "error"
  url?: string
  abortController?: AbortController
}

export interface UploadOptions {
  maxSize: number
  limit: number
  accept: string
  upload: (
    file: File,
    onProgress: (event: { progress: number }) => void,
    signal: AbortSignal
  ) => Promise<string>
  onSuccess?: (url: string) => void
  onError?: (error: Error) => void
}

export function useFileUpload(options: UploadOptions) {
  const fileItem = ref<FileItem | null>()

  const uploadFile = async (file: File): Promise<string | null> => {
    if (file.size > options.maxSize) {
      const error = new Error(
        `File size exceeds maximum allowed (${options.maxSize / 1024 / 1024}MB)`
      )
      options.onError?.(error)
      return null
    }

    const abortController = new AbortController()

    const newFileItem: FileItem = {
      id: randomString(),
      file,
      progress: 0,
      status: "uploading",
      abortController,
    }

    fileItem.value = newFileItem

    try {
      if (!options.upload) {
        throw new Error("Upload function is not defined")
      }

      const url = await options.upload(
        file,
        (event: { progress: number }) => {
          if (fileItem.value) {
            fileItem.value = {
              ...fileItem.value,
              progress: event.progress,
            }
          }
        },
        abortController.signal
      )

      if (!url) throw new Error("Upload failed: No URL returned")

      if (!abortController.signal.aborted) {
        if (fileItem.value) {
          fileItem.value = {
            ...fileItem.value,
            status: "success",
            url,
            progress: 100,
          }
        }
        options.onSuccess?.(url)
        return url
      }

      return null
    } catch (error) {
      if (!abortController.signal.aborted) {
        if (fileItem.value) {
          fileItem.value = {
            ...fileItem.value,
            status: "error",
            progress: 0,
          }
        }
        options.onError?.(
          error instanceof Error ? error : new Error("Upload failed")
        )
      }
      return null
    }
  }

  const uploadFiles = async (files: File[]): Promise<string | null> => {
    if (!files || files.length === 0) {
      options.onError?.(new Error("No files to upload"))
      return null
    }

    if (options.limit && files.length > options.limit) {
      options.onError?.(
        new Error(
          `Maximum ${options.limit} file${options.limit === 1 ? "" : "s"} allowed`
        )
      )
      return null
    }

    const file = files[0]
    if (!file) {
      options.onError?.(new Error("File is undefined"))
      return null
    }

    return uploadFile(file)
  }

  const clearFileItem = () => {
    if (!fileItem.value) return

    if (fileItem.value.abortController) {
      fileItem.value.abortController.abort()
    }
    if (fileItem.value.url) {
      URL.revokeObjectURL(fileItem.value.url)
    }
    fileItem.value = null
  }

  return {
    fileItem,
    uploadFiles,
    clearFileItem,
  }
}

export interface ImageUploadNodeOptions {
  /**
   * Acceptable file types for upload.
   * @default 'image/*'
   */
  accept?: string
  /**
   * Maximum number of files that can be uploaded.
   * @default 1
   */
  limit?: number
  /**
   * Maximum file size in bytes (0 for unlimited).
   * @default 0
   */
  maxSize?: number
  /**
   * Function to handle the upload process.
   */
  upload?: UploadFunction
  /**
   * Callback for upload errors.
   */
  onError?: (error: Error) => void
  /**
   * Callback for successful uploads.
   */
  onSuccess?: (url: string) => void
}

declare module "@tiptap/vue-3" {
  interface Commands<ReturnType> {
    imageUpload: {
      setImageUploadNode: (options?: ImageUploadNodeOptions) => ReturnType
    }
  }
}

/**
 * A TipTap node extension that creates an image upload component.
 */
export const ImageUploadNode = Node.create<ImageUploadNodeOptions>({
  name: "imageUpload",

  group: "block",

  draggable: true,

  selectable: true,

  atom: true,

  addOptions() {
    return {
      accept: "image/*",
      limit: 1,
      maxSize: 0,
      upload: undefined,
      onError: undefined,
      onSuccess: undefined,
    }
  },

  addAttributes() {
    return {
      accept: {
        default: this.options.accept,
      },
      limit: {
        default: this.options.limit,
      },
      maxSize: {
        default: this.options.maxSize,
      },
    }
  },

  parseHTML() {
    return [{ tag: 'div[data-type="image-upload"]' }]
  },

  renderHTML({ HTMLAttributes }) {
    return [
      "div",
      mergeAttributes({ "data-type": "image-upload" }, HTMLAttributes),
    ]
  },

  addNodeView() {
    return VueNodeViewRenderer(TiptapImageUploadNode)
  },

  addCommands() {
    return {
      setImageUploadNode:
        (options = {}) =>
          ({ commands }) => {
            return commands.insertContent({
              type: this.name,
              attrs: options,
            })
          },
    }
  },

  /**
   * Adds Enter key handler to trigger the upload component when it's selected.
   */
  addKeyboardShortcuts() {
    return {
      Enter: ({ editor }) => {
        const { selection } = editor.state
        const { nodeAfter } = selection.$from

        if (
          nodeAfter &&
          nodeAfter.type.name === "imageUpload" &&
          editor.isActive("imageUpload")
        ) {
          const nodeEl = editor.view.nodeDOM(selection.$from.pos)
          if (nodeEl && nodeEl instanceof HTMLElement) {
            // Since NodeViewWrapper is wrapped with a div, we need to click the first child
            const firstChild = nodeEl.firstChild
            if (firstChild && firstChild instanceof HTMLElement) {
              firstChild.click()
              return true
            }
          }
        }
        return false
      },
    }
  },
})
