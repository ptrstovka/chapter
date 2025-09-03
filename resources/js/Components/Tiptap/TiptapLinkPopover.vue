<template>
  <Popover v-if="show" v-model:open="isOpen">
    <PopoverTrigger as-child>
      <TiptapButton
        v-bind="forwarded"
        tabindex="-1"
        :aria-label="$t('Tiptap:Link')"
        :tooltip="$t('Tiptap:Link')"
        :disabled="isDisabled"
      >
        <Link2Icon />
      </TiptapButton>
    </PopoverTrigger>
    <PopoverContent class="w-92 p-1" @open-auto-focus.prevent @close-auto-focus.prevent>
      <div class="flex flex-row items-center gap-0.5">
        <Input
          class="text-sm border-0 shadow-none focus-visible:ring-0"
          :placeholder="$t('Tiptap:Paste a link…')"
          v-model="url"
          @keydown.enter="onEnter"
          autocomplete="off"
          autocapitalize="off"
          type="url"
        />

        <TiptapButton
          @click="setLink"
          :title="$t('Tiptap:Apply link')"
          :disabled="!url && !isActive"
        >
          <CornerDownLeftIcon />
        </TiptapButton>

        <div class="h-5 px-1">
          <Separator orientation="vertical" />
        </div>

        <TiptapButton
          @click="handleOpenLink"
          :title="$t('Tiptap:Open in new window')"
          :disabled="!url && !isActive"
        >
          <ExternalLinkIcon />
        </TiptapButton>

        <TiptapButton
          @click="removeLink"
          :title="$t('Tiptap:Remove link')"
          :disabled="!url && !isActive"
        >
          <TrashIcon />
        </TiptapButton>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { isNodeSelection } from "@tiptap/vue-3";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { type TiptapButtonProps, TiptapButton } from '.'
import { isMarkInSchema, sanitizeUrl, useTiptap } from "./utils";
import { Popover, PopoverTrigger, PopoverContent } from '@/Components/Popover'
import { Link2Icon, CornerDownLeftIcon, ExternalLinkIcon, TrashIcon } from 'lucide-vue-next'
import { Input } from '@/Components/Input'
import { Separator } from '@/Components/Separator'

const props = withDefaults(defineProps<TiptapButtonProps & {
  /**
   * Whether to hide the link popover.
   * @default false
   */
  hideWhenUnavailable?: boolean

  /**
   * Whether to automatically open the popover when a link is active.
   * @default true
   */
  autoOpenOnLinkActive?: boolean
}>(), {
  autoOpenOnLinkActive: true,
})

const delegatedProps = reactiveOmit(props, 'hideWhenUnavailable', 'autoOpenOnLinkActive')
const forwarded = useForwardProps(delegatedProps)

const { editor, disabled: editorDisabled } = useTiptap()
const url = ref<string>('')

const isOpen = ref(false)

const onEnter = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    setLink()
  }
}

const handleOpenLink = () => {
  if (url.value) {
    const safeUrl = sanitizeUrl(url.value, window.location.href)
    if (safeUrl !== '#') {
      window.open(safeUrl, '_blank', 'noopener,noreferrer')
    }
  }
}

const setLink = () => {
  if (!url.value || !editor.value) return

  editor.value.chain().focus().extendMarkRange("link").setLink({ href: url.value }).run()

  url.value = ''

  isOpen.value = false
}

const removeLink = () => {
  if (!editor.value) return

  editor
    .value
    .chain()
    .focus()
    .extendMarkRange("link")
    .unsetLink()
    .setMeta("preventAutolink", true)
    .run()
  url.value = ''
}

const linkInSchema = computed(() => isMarkInSchema('link', editor.value || null))

const isDisabled = computed(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (editor.value.isActive("codeBlock")) return true
  return !editor.value.can().setLink?.({ href: "" })
  return false
})

const canSetLink = computed(() => {
  if (!editor.value) return false

  try {
    return editor.value.can().setMark("link")
  } catch {
    return false
  }
})

const isActive = computed(() => !isDisabled.value && (editor.value?.isActive("link") ?? false))

const show = computed(() => {
  if (!linkInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection) || !canSetLink.value) {
      return false
    }
  }

  return true
})

const updateLinkState = () => {
  if (!editor.value) return

  const { href } = editor.value.getAttributes('link')
  url.value = href || ''

  if (editor.value.isActive('link') && url.value !== '') {
    isOpen.value = props.autoOpenOnLinkActive && !isDisabled.value
  } else {
    isOpen.value = false
  }
}

let cleanup: VoidFunction | undefined

watch(editor, editor => {
  if (editor) {
    if (cleanup) {
      cleanup()
      cleanup = undefined
    }

    editor.on('selectionUpdate', updateLinkState)
    cleanup = () => editor.off('selectionUpdate', updateLinkState)
  }
})

onBeforeUnmount(() => {
  if (cleanup) {
    cleanup()
    cleanup = undefined
  }
})
</script>
