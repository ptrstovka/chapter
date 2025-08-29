<template>
  <div>
    <EditorContent :editor="editor" />
  </div>
</template>

<script setup lang="ts">
import StarterKit from "@tiptap/starter-kit";
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { onBeforeUnmount, watch } from "vue";

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue: string | null | undefined
}>()

const editor = useEditor({
  extensions: [StarterKit],
  content: props.modelValue,
  onUpdate: () => {
    emit('update:modelValue', editor.value?.getHTML())
  },
  editorProps: {
    attributes: {
      class: 'prose prose-sm min-h-60 max-w-full px-3 py-3 focus:outline-none',
    },
  },
})

watch(() => props.modelValue, newModelValue => {
  if (editor.value?.getHTML() !== newModelValue) {
    editor.value?.commands.setContent(newModelValue as any)
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
  editor.value = undefined
})
</script>
