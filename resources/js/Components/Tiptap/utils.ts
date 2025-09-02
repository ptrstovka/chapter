import axios from "axios";
import { createContext } from 'reka-ui'
import type { Editor } from '@tiptap/vue-3'
import type { Node } from '@tiptap/pm/model'
import type { ComputedRef, ShallowRef } from "vue";

export const MAX_FILE_SIZE = 64 * 1024 * 1024 // 64MB

export const [useTiptap, provideTiptapContext] = createContext<{
  editor: ShallowRef<Editor | undefined>
  disabled: ComputedRef<boolean>
}>('Tiptap')

/**
 * Checks if a mark exists in the editor schema
 * @param markName - The name of the mark to check
 * @param editor - The editor instance
 * @returns boolean indicating if the mark exists in the schema
 */
export const isMarkInSchema = (
  markName: string,
  editor: Editor | null
): boolean => {
  if (!editor?.schema) return false
  return editor.schema.spec.marks.get(markName) !== undefined
}

/**
 * Checks if a node exists in the editor schema
 * @param nodeName - The name of the node to check
 * @param editor - The editor instance
 * @returns boolean indicating if the node exists in the schema
 */
export const isNodeInSchema = (
  nodeName: string,
  editor: Editor | null
): boolean => {
  if (!editor?.schema) return false
  return editor.schema.spec.nodes.get(nodeName) !== undefined
}

/**
 * Checks if a node is empty
 */
export function isEmptyNode(node?: Node | null): boolean {
  return !!node && node.content.size === 0
}

/**
 * Finds the position and instance of a node in the document
 * @param props Object containing editor, node (optional), and nodePos (optional)
 * @param props.editor The TipTap editor instance
 * @param props.node The node to find (optional if nodePos is provided)
 * @param props.nodePos The position of the node to find (optional if node is provided)
 * @returns An object with the position and node, or null if not found
 */
export function findNodePosition(props: {
  editor: Editor | null
  node?: Node | null
  nodePos?: number | null
}): { pos: number; node: Node } | null {
  const { editor, node, nodePos } = props

  if (!editor || !editor.state?.doc) return null

  // Zero is valid position
  const hasValidNode = node !== undefined && node !== null
  const hasValidPos = nodePos !== undefined && nodePos !== null

  if (!hasValidNode && !hasValidPos) {
    return null
  }

  if (hasValidPos) {
    try {
      const nodeAtPos = editor.state.doc.nodeAt(nodePos!)
      if (nodeAtPos) {
        return { pos: nodePos!, node: nodeAtPos }
      }
    } catch (error) {
      console.error("Error checking node at position:", error)
      return null
    }
  }

  // Otherwise search for the node in the document
  let foundPos = -1
  let foundNode: Node | null = null

  editor.state.doc.descendants((currentNode, pos) => {
    // TODO: Needed?
    // if (currentNode.type && currentNode.type.name === node!.type.name) {
    if (currentNode === node) {
      foundPos = pos
      foundNode = currentNode
      return false
    }
    return true
  })

  return foundPos !== -1 && foundNode !== null
    ? { pos: foundPos, node: foundNode }
    : null
}

/**
 * Handles image upload with progress tracking and abort capability
 * @param file The file to upload
 * @param onProgress Optional callback for tracking upload progress
 * @param abortSignal Optional AbortSignal for cancelling the upload
 * @returns Promise resolving to the URL of the uploaded image
 */
export const handleImageUpload = async (
  file: File,
  onProgress?: (event: { progress: number }) => void,
  abortSignal?: AbortSignal
): Promise<string> => {
  // Validate file
  if (!file) {
    throw new Error("No file provided")
  }

  if (file.size > MAX_FILE_SIZE) {
    throw new Error(
      `File size exceeds maximum allowed (${MAX_FILE_SIZE / (1024 * 1024)}MB)`
    )
  }

  const data = new FormData()
  data.set('file', file)
  const response = await axios.post<{ url: string }>(route('tiptap.images.store'), data, {
    signal: abortSignal,
    onUploadProgress: event => {
      if (event.lengthComputable) {
        onProgress?.({ progress: Math.round((event.loaded / (event.total || 1)) * 100) })
      }
    }
  })

  return response.data.url
}

type ProtocolOptions = {
  /**
   * The protocol scheme to be registered.
   * @default '''
   * @example 'ftp'
   * @example 'git'
   */
  scheme: string

  /**
   * If enabled, it allows optional slashes after the protocol.
   * @default false
   * @example true
   */
  optionalSlashes?: boolean
}

type ProtocolConfig = Array<ProtocolOptions | string>

const ATTR_WHITESPACE =
  // eslint-disable-next-line no-control-regex
  /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g

export function isAllowedUri(
  uri: string | undefined,
  protocols?: ProtocolConfig
) {
  const allowedProtocols: string[] = [
    "http",
    "https",
    "ftp",
    "ftps",
    "mailto",
    "tel",
    "callto",
    "sms",
    "cid",
    "xmpp",
  ]

  if (protocols) {
    protocols.forEach((protocol) => {
      const nextProtocol =
        typeof protocol === "string" ? protocol : protocol.scheme

      if (nextProtocol) {
        allowedProtocols.push(nextProtocol)
      }
    })
  }

  return (
    !uri ||
    uri.replace(ATTR_WHITESPACE, "").match(
      new RegExp(
        // eslint-disable-next-line no-useless-escape
        `^(?:(?:${allowedProtocols.join("|")}):|[^a-z]|[a-z0-9+.\-]+(?:[^a-z+.\-:]|$))`,
        "i"
      )
    )
  )
}

export function sanitizeUrl(
  inputUrl: string,
  baseUrl: string,
  protocols?: ProtocolConfig
): string {
  try {
    const url = new URL(inputUrl, baseUrl)

    if (isAllowedUri(url.href, protocols)) {
      return url.href
    }
  } catch {
    // If URL creation fails, it's considered invalid
  }
  return "#"
}
