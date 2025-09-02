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
    <component :is="icon" />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { isNodeSelection } from "@tiptap/vue-3";
import { isNodeInSchema, useTiptap } from "./utils.ts";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { type TiptapButtonProps, TiptapButton } from '.'
import { ListIcon, ListOrderedIcon, ListTodoIcon } from 'lucide-vue-next'

type ListType = "bulletList" | "orderedList" | "taskList"

const listShortcutKeys: Record<ListType, string> = {
  bulletList: "Ctrl-Shift-8",
  orderedList: "Ctrl-Shift-7",
  taskList: "Ctrl-Shift-9",
}

const listLabels: Record<ListType, string> = {
  bulletList: "Bullet List",
  orderedList: "Ordered List",
  taskList: "Task List",
}

const listIcons = {
  bulletList: ListIcon,
  orderedList: ListOrderedIcon,
  taskList: ListTodoIcon,
}

const emit = defineEmits(['click'])

const props = defineProps<TiptapButtonProps & {
  /**
   * The type of list to toggle.
   */
  type: ListType

  /**
   * Optional text to display alongside the icon.
   */
  text?: string

  /**
   * Whether the button should hide when the list is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean
}>()

const { editor, disabled: editorDisabled } = useTiptap()

const delegatedProps = reactiveOmit(props, 'type', 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const canToggleList = computed<boolean>(() => {
  if (! editor.value) {
    return false
  }

  switch (props.type) {
    case "bulletList":
      return editor.value.can().toggleBulletList()
    case "orderedList":
      return editor.value.can().toggleOrderedList()
    case "taskList":
      return editor.value.can().toggleList("taskList", "taskItem")
    default:
      return false
  }
})

const isActive = computed<boolean>(() => {
  if (! editor.value) {
    return false
  }

  switch (props.type) {
    case "bulletList":
      return editor.value.isActive("bulletList")
    case "orderedList":
      return editor.value.isActive("orderedList")
    case "taskList":
      return editor.value.isActive("taskList")
    default:
      return false
  }
})

const toggleList = () => {
  if (!editor.value) {
    return
  }

  switch (props.type) {
    case "bulletList":
      editor.value.chain().focus().toggleBulletList().run()
      break
    case "orderedList":
      editor.value.chain().focus().toggleOrderedList().run()
      break
    case "taskList":
      editor.value.chain().focus().toggleList("taskList", "taskItem").run()
      break
  }
}

const listInSchema = computed(() => isNodeInSchema(props.type, editor.value || null))

const show = computed<boolean>(() => {
  if (!listInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection) || !canToggleList.value) {
      return false
    }
  }

  return true
})

const shortcutKey = computed(() => listShortcutKeys[props.type])
const icon = computed(() => listIcons[props.type])
const label = computed(() => listLabels[props.type])
const isDisabled = computed(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (!canToggleList.value) return true
  return false
})

const handleClick = (event: MouseEvent) => {
  emit('click', event)

  if (!event.defaultPrevented && editor.value) {
    toggleList()
  }
}
</script>
