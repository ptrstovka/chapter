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
import { ListKit } from '@tiptap/extension-list'
import { Subscript } from "@tiptap/extension-subscript"
import { Superscript } from "@tiptap/extension-superscript"
import { TextAlign } from "@tiptap/extension-text-align"
import { Link } from "./Extension/link-extension"
import { Selection } from "./Extension/selection-extension"
import { TrailingNode } from "./Extension/trailing-node-extension"
import StarterKit from "@tiptap/starter-kit";

const emit = defineEmits<TiptapProviderEmits>()
const props = defineProps<TiptapProviderProps>()

const editor = useEditor({
  extensions: [
    StarterKit,

    ListKit.configure({
      taskList: false,
    }),
    Subscript,
    Superscript,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),

    Selection,
    // TrailingNode,
    Link.configure({ openOnClick: false }),
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
      autocomplete: "off",
      autocorrect: "off",
      autocapitalize: "off",
      "aria-label": "Main content area, start typing to enter text.",
      class: 'prose dark:prose-invert prose-sm min-h-60 max-w-full px-3 py-3 focus:outline-none selection:bg-primary selection:text-primary-foreground',
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

<style>
.tiptap.ProseMirror .selection {
  display: inline;
  background-color: var(--primary);
  color: var(--primary-foreground);
}
</style>
