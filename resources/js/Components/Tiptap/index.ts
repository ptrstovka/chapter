export { default as TiptapButton } from './TiptapButton.vue'
export { default as TiptapContent } from './TiptapContent.vue'
export { default as TiptapEditor } from './TiptapEditor.vue'
export { default as TiptapMarkButton } from './TiptapMarkButton.vue'
export { default as TiptapProvider } from './TiptapProvider.vue'
export { default as TiptapToolbar } from './TiptapToolbar.vue'

export interface TiptapProviderEmits {
  'update:modelValue': [payload: string | null | undefined];
}

export interface TiptapProviderProps {
  modelValue?: string | null | undefined
  disabled?: boolean
}
