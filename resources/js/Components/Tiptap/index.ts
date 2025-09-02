import type { ButtonProps } from "@/Components/Button";

export { default as TiptapBlockquoteButton } from './TiptapBlockquoteButton.vue'
export { default as TiptapButton } from './TiptapButton.vue'
export { default as TiptapCodeBlockButton } from './TiptapCodeBlockButton.vue'
export { default as TiptapContent } from './TiptapContent.vue'
export { default as TiptapHeadingButton } from './TiptapHeadingButton.vue'
export { default as TiptapHeadingDropdownMenu } from './TiptapHeadingDropdownMenu.vue'
export { default as TiptapLinkPopover } from './TiptapLinkPopover.vue'
export { default as TiptapListButton } from './TiptapListButton.vue'
export { default as TiptapListDropdownMenu } from './TiptapListDropdownMenu.vue'
export { default as TiptapMarkButton } from './TiptapMarkButton.vue'
export { default as TiptapProvider } from './TiptapProvider.vue'
export { default as TiptapSimpleEditor } from './TiptapSimpleEditor.vue'
export { default as TiptapTextAlignButton } from './TiptapTextAlignButton.vue'
export { default as TiptapToolbar } from './TiptapToolbar.vue'
export { default as TiptapToolbarSeparator } from './TiptapToolbarSeparator.vue'

export interface TiptapProviderEmits {
  'update:modelValue': [payload: string | null | undefined];
}

export interface TiptapProviderProps {
  modelValue?: string | null | undefined
  disabled?: boolean
}

export interface TiptapButtonProps extends ButtonProps {
  active?: boolean
  tooltip?: string | null | undefined
  shortcut?: string | null | undefined
}
