<template>
  <Popover v-if="show" v-model:open="isOpen">
    <PopoverTrigger as-child>
      <TiptapButton
        v-bind="forwarded"
        tabindex="-1"
        aria-label="Highlight text"
        tooltip="Highlight"
        :disabled="isDisabled"
        :active="isActive"
      >
        <HighlighterIcon />
      </TiptapButton>
    </PopoverTrigger>
    <PopoverContent @open-auto-focus.prevent @close-auto-focus.prevent class="w-auto p-2">
      <div class="flex flex-row gap-0.5 items-center">
        <TiptapColorHighlightButton
          v-for="color in colors"
          :color="color.value"
          :aria-label="`${color.value} highlight color`"
        />

        <div class="h-5 px-1">
          <Separator orientation="vertical" />
        </div>

        <TiptapButton
          @click="removeHighlight"
          title="Remove highlight"
        >
          <BanIcon />
        </TiptapButton>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { Popover, PopoverTrigger, PopoverContent } from "@/Components/Popover";
import { Separator } from "@/Components/Separator";
import { isNodeSelection } from "@tiptap/vue-3";
import { TiptapButton, type TiptapButtonProps, TiptapColorHighlightButton, HIGHLIGHT_COLORS } from ".";
import { isMarkInSchema, useTiptap } from "./utils";
import { reactiveOmit } from "@vueuse/core";
import { BanIcon, HighlighterIcon } from "lucide-vue-next";
import { useForwardProps } from "reka-ui";
import { computed, ref } from "vue";

interface ColorHighlightPopoverColor {
  label: string
  value: string
  border?: string
}

const props = withDefaults(defineProps<TiptapButtonProps & {
  /**
   * Whether the button should hide when the mark is not available.
   * @default false
   */
  hideWhenUnavailable?: boolean

  /**
   * Default selection of colors.
   */
  colors?: ColorHighlightPopoverColor[]
}>(), {
  colors: () => HIGHLIGHT_COLORS,
})

const delegatedProps = reactiveOmit(props, 'colors', 'hideWhenUnavailable')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const isOpen = ref(false)

const markAvailable = computed(() =>isMarkInSchema('highlight', editor.value || null))

const isDisabled = computed(() => {
  if (!editor.value || props.disabled || editorDisabled.value || !markAvailable.value) return true

  const isIncompatibleContext =
    editor.value.isActive("code") ||
    editor.value.isActive("codeBlock") ||
    editor.value.isActive("imageUpload")

  return isIncompatibleContext || !canToggleHighlight.value
})

const isActive = computed(() => !isDisabled.value && (editor.value?.isActive("highlight") ?? false))

const canToggleHighlight = computed(() => {
  if (!editor.value) return false
  try {
    return editor.value.can().setMark("highlight")
  } catch {
    return false
  }
})

const show = computed(() => {
  if (!props.hideWhenUnavailable || !editor.value) return true

  return !(isNodeSelection(editor.value.state.selection) || !canToggleHighlight.value)
})

const removeHighlight = () => {
  if (!editor.value) return
  editor.value.chain().focus().unsetMark("highlight").run()
}
</script>
