<template>
  <TiptapButton
    v-if="show"
    v-bind="forwarded"
    :disabled="isDisabled"
    :active="isActive"
    tabindex="-1"
    :aria-label="$t('Tiptap:AriaLabel :color highlight color', { color })"
    :aria-pressed="isActive"
    @click="handleClick"
  >
    <span class="size-3.5 rounded-full bg-(--highlight-color)" :style="{ '--highlight-color': color }" />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { isNodeSelection } from "@tiptap/vue-3";
import { useTiptap, isEmptyNode, findNodePosition, isMarkInSchema } from "./utils.ts";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { type TiptapButtonProps, TiptapButton } from '.'
import type { Node } from '@tiptap/pm/model'

const emit = defineEmits(['click', 'applied'])

const props = withDefaults(defineProps<TiptapButtonProps & {
  /**
   * The node to apply highlight to
   */
  node?: Node | null

  /**
   * The position of the node in the document
   */
  nodePos?: number | null

  /**
   * The color to apply when toggling the highlight.
   * If not provided, it will use the default color from the extension.
   */
  color: string

  /**
   * Optional text to display alongside the icon.
   */
  text?: string

  /**
   * Whether the button should hide when the mark is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean
}>(), {
  hideWhenUnavailable: false,
})

const delegatedProps = reactiveOmit(props, 'node', 'nodePos', 'color', 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const canToggleHighlight = computed(() => {
  if (!editor.value) return false
  try {
    return editor.value.can().setMark("highlight")
  } catch {
    return false
  }
})

const toggleHighlight = () => {
  if (!editor.value) return

  try {
    const node = props.node
    const nodePos = props.nodePos
    const color = props.color

    const chain = editor.value.chain().focus()

    if (isEmptyNode(node)) {
      chain.toggleMark("highlight", { color }).run()
    } else if (nodePos !== undefined && nodePos !== null && nodePos !== -1) {
      chain.setNodeSelection(nodePos).toggleMark("highlight", { color }).run()
    } else if (node) {
      const foundPos = findNodePosition({ editor: editor.value, node })
      if (foundPos) {
        chain
          .setNodeSelection(foundPos.pos)
          .toggleMark("highlight", { color })
          .run()
      } else {
        chain.toggleMark("highlight", { color }).run()
      }
    } else {
      chain.toggleMark("highlight", { color }).run()
    }

    editor.value.chain().setMeta("hideDragHandle", true).run()
  } catch (error) {
    console.error("Failed to apply highlight:", error)
  }
}

const isDisabled = computed(() => {
  if (!editor.value || props.disabled || editorDisabled.value) return true

  const isIncompatibleContext =
    editor.value.isActive("code") ||
    editor.value.isActive("codeBlock") ||
    editor.value.isActive("imageUpload")

  return isIncompatibleContext || !canToggleHighlight.value
})

const isActive = computed(() => {
  if (!editor.value) return false
  return !isDisabled.value && editor.value.isActive("highlight", { color: props.color })
})

const highlightInSchema = computed(() => isMarkInSchema('highlight', editor.value || null))

const show = computed(() => {
  if (!highlightInSchema.value || !editor.value) return false

  if (props.hideWhenUnavailable) {
    if (
      isNodeSelection(editor.value.state.selection) ||
      !canToggleHighlight.value
    ) {
      return false
    }
  }

  return true
})

const handleClick = (event: MouseEvent) => {
  emit('click')

  if (!event.defaultPrevented && !isDisabled.value && editor.value) {
    toggleHighlight()
    emit('applied', props.color)
  }
}
</script>
