<template>
  <div :class="cn(
    'dark:bg-input/30 border-input rounded-md border bg-transparent transition-[color,box-shadow] outline-none disabled:bg-accent/50',
  )">
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { TiptapProviderProps, TiptapProviderEmits } from ".";
import { cn } from "@/Utils";
import { useEditor } from "@tiptap/vue-3";
import { computed, watch } from "vue";
import { provideTiptapContext } from './utils.ts'
import { Subscript } from "@tiptap/extension-subscript"
import { Superscript } from "@tiptap/extension-superscript"
import StarterKit from "@tiptap/starter-kit";

const emit = defineEmits<TiptapProviderEmits>()
const props = defineProps<TiptapProviderProps>()

const editor = useEditor({
  extensions: [
    StarterKit,

    Subscript,
    Superscript,
  ],
  content: props.modelValue,
  editable: props.disabled === true ? false : true,
  onUpdate: () => {
    const html = editor.value?.getHTML()
    if (props.modelValue !== html) {
      emit('update:modelValue', html)
    }
  },
  editorProps: {
    attributes: {
      class: 'prose dark:prose-invert prose-sm min-h-60 max-w-full px-3 py-3 focus:outline-none',
    },
  },
})

watch(() => props.modelValue, newModelValue => {
  if (editor.value?.getHTML() !== newModelValue) {
    editor.value?.commands.setContent(newModelValue as any)
  }
})

watch(() => props.disabled, isDisabled => {
  if (isDisabled === true) {
    editor.value?.setEditable(false)
  } else {
    editor.value?.setEditable(true)
  }
})

const isDisabled = computed(() => !!props.disabled)

provideTiptapContext({
  editor,
  disabled: isDisabled,
})
</script>
