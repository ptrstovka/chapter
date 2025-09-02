import type { ButtonProps } from "@/Components/Button";

export { default as TiptapBlockquoteButton } from './TiptapBlockquoteButton.vue'
export { default as TiptapButton } from './TiptapButton.vue'
export { default as TiptapCodeBlockButton } from './TiptapCodeBlockButton.vue'
export { default as TiptapColorHighlightButton } from './TiptapColorHighlightButton.vue'
export { default as TiptapColorHighlightPopover } from './TiptapColorHighlightPopover.vue'
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

export interface TiptapButtonProps extends Omit<ButtonProps, 'variant'> {
  active?: boolean
  tooltip?: string | null | undefined
  shortcut?: string | null | undefined
}

export const HIGHLIGHT_COLORS = [
  {
    label: "Gray",
    value: "var(--tt-color-text-gray)",
    border: "var(--tt-color-text-gray-contrast)",
  },
  {
    label: "Brown",
    value: "var(--tt-color-text-brown)",
    border: "var(--tt-color-text-brown-contrast)",
  },
  {
    label: "Orange",
    value: "var(--tt-color-text-orange)",
    border: "var(--tt-color-text-orange-contrast)",
  },
  {
    label: "Yellow",
    value: "var(--tt-color-text-yellow)",
    border: "var(--tt-color-text-yellow-contrast)",
  },
  {
    label: "Green",
    value: "var(--tt-color-text-green)",
    border: "var(--tt-color-text-green-contrast)",
  },
  {
    label: "Blue",
    value: "var(--tt-color-text-blue)",
    border: "var(--tt-color-text-blue-contrast)",
  },
  {
    label: "Purple",
    value: "var(--tt-color-text-purple)",
    border: "var(--tt-color-text-purple-contrast)",
  },
  {
    label: "Pink",
    value: "var(--tt-color-text-pink)",
    border: "var(--tt-color-text-pink-contrast)",
  },
  {
    label: "Red",
    value: "var(--tt-color-text-red)",
    border: "var(--tt-color-text-red-contrast)",
  },
]
