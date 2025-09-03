<template>
  <TiptapButton
    v-if="show"
    v-bind="forwarded"
    @click="handleClick"
    :disabled="isDisabled"
    :active="isActive"
    tabindex="-1"
    :aria-label="formattedName"
    :aria-pressed="isActive"
    :tooltip="formattedName"
    :shortcut="shortcutKey"
  >
    <component :is="icon" />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { isNodeSelection } from "@tiptap/vue-3";
import { reactiveOmit } from "@vueuse/core";
import { wTrans } from "laravel-vue-i18n";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { TiptapButton, type TiptapButtonProps } from '.'
import { Heading1Icon, Heading2Icon, Heading3Icon, Heading4Icon, Heading5Icon, Heading6Icon } from 'lucide-vue-next'
import { isNodeInSchema, useTiptap } from "./utils.ts";

type Level = 1 | 2 | 3 | 4 | 5 | 6

const headingIcons = {
  1: Heading1Icon,
  2: Heading2Icon,
  3: Heading3Icon,
  4: Heading4Icon,
  5: Heading5Icon,
  6: Heading6Icon,
}

const headingShortcutKeys: Partial<Record<Level, string>> = {
  1: "Ctrl-Alt-1",
  2: "Ctrl-Alt-2",
  3: "Ctrl-Alt-3",
  4: "Ctrl-Alt-4",
  5: "Ctrl-Alt-5",
  6: "Ctrl-Alt-6",
}

const emit = defineEmits(['click'])

const props = defineProps<TiptapButtonProps & {
  /**
   * The heading level.
   */
  level: Level

  /**
   * Optional text to display alongside the icon.
   */
  text?: string

  /**
   * Whether the button should hide when the heading is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean
}>()

const { editor, disabled: editorDisabled } = useTiptap()

const delegatedProps = reactiveOmit(props, 'level', 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const canToggleHeading = () => {
  if (editor.value) {
    try {
      return editor.value.can().toggleNode("heading", "paragraph", { level: props.level })
    } catch {
      return false
    }
  }

  return false
}

const toggleHeading = () => {
  if (editor.value) {
    if (editor.value.isActive("heading", { level: props.level })) {
      editor.value.chain().focus().setNode("paragraph").run()
    } else {
      editor.value.chain().focus().toggleNode("heading", "paragraph", { level: props.level }).run()
    }
  }
}

const headingInSchema = computed(() => isNodeInSchema('heading', editor.value || null))

const isDisabled = computed<boolean>(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (!canToggleHeading()) return true
  return false
})

const isActive = computed<boolean>(() => {
  if (editor.value) {
    return editor.value.isActive('heading', { level: props.level })
  }

  return false
})

const icon = computed(() => headingIcons[props.level])

const shortcutKey = computed(() => headingShortcutKeys[props.level])
const formattedName = wTrans('Tiptap:Heading :value', { value: `${props.level}` })

const show = computed(() => {
  if (!headingInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection)) {
      return false
    }
  }

  return true
})

const handleClick = (event: MouseEvent) => {
  emit('click', event)

  if (!event.defaultPrevented && !isDisabled.value && editor.value) {
    toggleHeading()
  }
}
</script>
