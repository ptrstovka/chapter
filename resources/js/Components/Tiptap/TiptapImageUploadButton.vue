<template>
  <TiptapButton
    v-bind="forwarded"
    @click="handleClick"
    :disabled="isDisabled"
    :active="isActive"
    tabindex="-1"
    :aria-label="label"
    :aria-pressed="isActive"
    :tooltip="label"
  >
    <ImagePlusIcon />
    <template v-if="text">{{ text }}</template>
  </TiptapButton>
</template>

<script setup lang="ts">
import { reactiveOmit } from "@vueuse/core";
import { wTrans } from "laravel-vue-i18n";
import { useForwardProps } from "reka-ui";
import { computed } from "vue";
import { useTiptap } from "./utils.ts";
import { TiptapButton, type TiptapButtonProps } from '.'
import { ImagePlusIcon } from 'lucide-vue-next'

const emit = defineEmits(['click'])
const props = defineProps<TiptapButtonProps & {
  /**
   * Optional text to display alongside the icon.
   */
  text?: string
}>()

const delegatedProps = reactiveOmit(props, 'text')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()

const isDisabled = computed(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  return false
})

const isActive = computed(() => {
  if (!editor.value) return false
  return !isDisabled.value && editor.value.isActive("imageUpload")
})

const handleInsertImage = () => {
  if (!editor.value) return false

  return editor
    .value
    .chain()
    .focus()
    .insertContent({
      type: "imageUpload",
    })
    .run()
}

const label = wTrans('Tiptap:Image')

const handleClick = (event: MouseEvent) => {
  emit('click', event)

  if (!event.defaultPrevented && !isDisabled.value) {
    handleInsertImage()
  }
}
</script>
