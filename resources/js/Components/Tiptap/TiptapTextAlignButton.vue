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
import { useTiptap } from "@/Components/Tiptap/utils.ts";
import type { ChainedCommands, CanCommands } from "@tiptap/vue-3";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { TiptapButton, type TiptapButtonProps } from '.'
import { AlignLeftIcon, AlignRightIcon, AlignCenterIcon, AlignJustifyIcon } from 'lucide-vue-next'

type TextAlign = "left" | "center" | "right" | "justify"

const textAlignIcons = {
  left: AlignLeftIcon,
  center: AlignCenterIcon,
  right: AlignRightIcon,
  justify: AlignJustifyIcon,
}

const textAlignShortcutKeys: Partial<Record<TextAlign, string>> = {
  left: "Ctrl-Shift-l",
  center: "Ctrl-Shift-e",
  right: "Ctrl-Shift-r",
  justify: "Ctrl-Shift-j",
}

const textAlignLabels: Record<TextAlign, string> = {
  left: "Align left",
  center: "Align center",
  right: "Align right",
  justify: "Align justify",
}

const emit = defineEmits(['click'])

const props = defineProps<TiptapButtonProps & {
  /**
   * The text alignment to apply.
   */
  align: TextAlign

  /**
   * Optional text to display alongside the icon.
   */
  text?: string

  /**
   * Whether the button should hide when the alignment is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean
}>()

const delegatedProps = reactiveOmit(props, 'align', 'text', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const hasSetTextAlign = (commands: ChainedCommands): commands is ChainedCommands & { setTextAlign: (align: TextAlign) => ChainedCommands } => {
  return "setTextAlign" in commands
}

const hasCanSetTextAlign = (commands: CanCommands): commands is CanCommands & { setTextAlign: (align: TextAlign) => boolean } => {
  return "setTextAlign" in commands
}

const checkTextAlignExtension = () => {
  if (editor.value) {
    const hasExtension = editor.value.extensionManager.extensions.some((extension) => extension.name === "textAlign")

    if (!hasExtension) {
      console.warn(
        "TextAlign extension is not available. " +
        "Make sure it is included in your editor configuration."
      )
    }

    return hasExtension
  }

  return false
}

const canSetTextAlign = (alignAvailable: boolean): boolean => {
  if (editor.value && alignAvailable) {
    try {
      const chain = editor.value.can()

      if (hasCanSetTextAlign(chain)) {
        return chain.setTextAlign(props.align)
      }

      return false
    } catch {
      return false
    }
  }

  return false
}

const alignAvailable = computed(() => checkTextAlignExtension())
const canAlign = computed(() => canSetTextAlign(alignAvailable.value))
const isDisabled = computed(() => {
  if (!editor.value || !alignAvailable.value) return true
  if (editorDisabled.value) return true
  if (!canAlign.value) return true
  return false
})
const isActive = computed(() => {
  if (editor.value) {
    return !isDisabled.value && editor.value.isActive({ textAlign: props.align })
  }

  return false
})

const handleAlignment = () => {
  if (!alignAvailable.value || !editor.value || isDisabled.value) {
    return false
  }

  const chain = editor.value.chain().focus()

  if (hasSetTextAlign(chain)) {
    return chain.setTextAlign(props.align).run()
  }

  return false
}

const show = computed(() => {
  if (!editor.value) return false
  if (props.hideWhenUnavailable && !canAlign.value) return false
  return true
})

const icon = computed(() => textAlignIcons[props.align])
const shortcutKey = computed(() => textAlignShortcutKeys[props.align])
const label = computed(() => textAlignLabels[props.align])

const handleClick = (event: MouseEvent) => {
  emit('click', event)

  if (!event.defaultPrevented && !isDisabled.value) {
    handleAlignment()
  }
}
</script>
