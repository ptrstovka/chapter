<template>
  <NodeViewWrapper>
    <Dropzone
      @files="onFiles"
      :multiple="false"
      :processing="fileItem?.status === 'uploading'"
    />
  </NodeViewWrapper>
</template>

<script setup lang="ts">
import type { NodeViewProps } from '@tiptap/vue-3'
import { NodeViewWrapper } from '@tiptap/vue-3'
import { Dropzone } from '@/Components/Dropzone'
import { type UploadOptions, useFileUpload } from './Extension/image-upload-node-extension.ts'

const props = defineProps<NodeViewProps>()

const extension = props.extension

const uploadOptions: UploadOptions = {
  maxSize: props.node.attrs.maxSize,
  limit: props.node.attrs.limit,
  accept: props.node.attrs.accept,
  upload: extension.options.upload,
  onSuccess: extension.options.onSuccess,
  onError: extension.options.onError,
}

const { fileItem, uploadFiles } = useFileUpload(uploadOptions)

const onFiles = async (files: Array<File>) => {
  const url = await uploadFiles(files)

  if (url) {
    const pos = props.getPos()

    if (pos !== undefined) {
      const filename = files[0]?.name.replace(/\.[^/.]+$/, "") || "unknown"

      props.editor
        .chain()
        .focus()
        .deleteRange({ from: pos, to: pos + 1 })
        .insertContentAt(pos, [
          {
            type: "image",
            attrs: { src: url, alt: filename, title: filename },
          },
        ])
        .run()
    }
  }
}
</script>

<style>
.tiptap.ProseMirror .group\/dropzone *::selection {
  background: transparent;
  color: inherit;
}
</style>
