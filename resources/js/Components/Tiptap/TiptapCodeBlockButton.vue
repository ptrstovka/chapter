<template>
  <TiptapButton
    v-if="show"
    v-bind="forwarded"
    @click="handleClick"
    :disabled="isDisabled"
    :active="isActive"
    tabindex="-1"
    :aria-label="label"
    :aria-pressed="isActive"
    :tooltip="label"
    :shortcut="shortcutKey"
  >
    <SquareCodeIcon />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { isNodeSelection } from "@tiptap/vue-3";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { useTiptap, isNodeInSchema } from "./utils.ts";
import { TiptapButton, type TiptapButtonProps } from '.'
import { SquareCodeIcon } from 'lucide-vue-next'

const emit = defineEmits(['click'])
const props = defineProps<TiptapButtonProps & {
  /**
   * Optional text to display alongside the icon.
   */
  text?: string

  /**
   * Whether the button should hide when the node is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean
}>()

const delegatedProps = reactiveOmit(props, 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const canToggle = computed(() => {
  if (!editor.value) return false

  try {
    return editor.value.can().toggleNode("codeBlock", "paragraph")
  } catch {
    return false
  }
})

const toggle = () => {
  if (!editor.value) return false
  return editor.value.chain().focus().toggleNode("codeBlock", "paragraph").run()
}

const isDisabled = computed(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (!canToggle.value) return true
  return false
})

const isActive = computed(() => {
  if (!editor.value) return false
  return !isDisabled.value && editor.value.isActive("codeBlock")
})

const nodeInSchema = computed(() => isNodeInSchema("codeBlock", editor.value || null))

const show = computed(() => {
  if (!nodeInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection) || !canToggle.value) {
      return false
    }
  }

  return true
})

const shortcutKey = "Ctrl-Alt-c"
const label = "Code Block"

const handleClick = (event: MouseEvent) => {
  emit('click', event)

  if (!event.defaultPrevented && !isDisabled.value) {
    toggle()
  }
}
</script>
