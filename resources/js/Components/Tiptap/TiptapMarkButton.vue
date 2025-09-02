<template>
  <TiptapButton
    v-if="show"
    v-bind="forwarded"
    @click="handleClick"
    :disabled="isDisabled"
    :active="isActive"
    tabindex="-1"
    :aria-label="type"
    :aria-pressed="isActive"
    :tooltip="formattedName"
    :shortcut="shortcutKey"
  >
    <component :is="icon" />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { type ButtonProps } from '@/Components/Button'
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { useTiptap, isMarkInSchema } from './utils.ts'
import { TiptapButton } from '.'
import { isNodeSelection } from '@tiptap/vue-3'
import {
  BoldIcon,
  ItalicIcon,
  UnderlineIcon,
  StrikethroughIcon,
  CodeIcon,
  SubscriptIcon,
  SuperscriptIcon
} from 'lucide-vue-next'

type Mark =
  | "bold"
  | "italic"
  | "strike"
  | "code"
  | "underline"
  | "superscript"
  | "subscript"

const markShortcutKeys: Partial<Record<Mark, string>> = {
  bold: "Ctrl-b",
  italic: "Ctrl-i",
  underline: "Ctrl-u",
  strike: "Ctrl-Shift-s",
  code: "Ctrl-e",
  superscript: "Ctrl-.",
  subscript: "Ctrl-,",
}

const markIcons = {
  bold: BoldIcon,
  italic: ItalicIcon,
  underline: UnderlineIcon,
  strike: StrikethroughIcon,
  code: CodeIcon,
  superscript: SuperscriptIcon,
  subscript: SubscriptIcon,
}

const emit = defineEmits(['click'])

const props = withDefaults(defineProps<ButtonProps & {
  /**
   * The type of mark to toggle
   */
  type: Mark

  /**
   * Display text for the button (optional)
   */
  text?: string

  /**
   * Whether this button should be hidden when the mark is not available
   * @default false
   */
  hideWhenUnavailable?: boolean
}>(), {
  hideWhenUnavailable: false
})

const delegatedProps = reactiveOmit(props, 'type', 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const canToggleMark = (type: Mark) => {
  const e = editor.value
  if (!e) return false

  try {
    return e.can().toggleMark(type)
  } catch {
    return false
  }
}

const markInSchema = computed(() => isMarkInSchema(props.type, editor.value || null))

const isDisabled = computed<boolean>(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (editor.value.isActive('codeBlock')) return true
  if (!canToggleMark(props.type)) return true
  return false
})

const isActive = computed<boolean>(() => {
  if (editor.value) {
    return !isDisabled.value && editor.value.isActive(props.type)
  }

  return false
})

const icon = computed(() => markIcons[props.type])
const shortcutKey = computed(() => markShortcutKeys[props.type])
const formattedName = computed<string>(() => props.type.charAt(0).toUpperCase() + props.type.slice(1))

const show = computed(() => {
  const e = editor.value

  if (!markInSchema.value || !e) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(e.state.selection) || !canToggleMark(props.type)) {
      return false
    }
  }

  return true
})

const handleClick = (e: MouseEvent) => {
  emit('click', e)

  if (!e.defaultPrevented && !isDisabled.value) {
    editor.value?.chain().focus().toggleMark(props.type).run()
  }
}
</script>
