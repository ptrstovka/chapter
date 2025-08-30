<template>
  <div ref="zoneEl" :class="cn(
    'relative flex flex-col w-full items-center justify-center text-sm border overflow-hidden border-2 border-dashed rounded-md py-8 px-3 transition-all',
    isOverDropZone && !disabled ? 'border-ring' : 'border-input',
    { 'opacity-80 cursor-not-allowed text-muted-foreground': disabled },
    $attrs.class || ''
  )">
    <slot>
      <UploadIcon v-if="showIcon" class="size-6 mb-2 text-primary" />
      <span class="font-medium">{{ $t(dragLabel) }}</span>
      <span class="text-muted-foreground mt-2">{{ $t(orLabel) }}</span>
      <Button :disabled="disabled" @click.prevent.stop="selectFile" variant="link">{{ $t(pickLabel) }}</Button>
      <input ref="inputEl" type="file" class="hidden" :multiple="multiple" :accept="accept" @change="onInputChange">

      <div v-if="processing" class="bg-background absolute inset-0 flex flex-col items-center justify-center">
        <span class="font-medium mb-4">{{ $t('Uploading…') }}</span>
        <Spinner class="size-4" />
      </div>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { cn } from '@/Utils'
import { Button } from '@/Components/Button'
import { UploadIcon } from 'lucide-vue-next'
import { useDropZone } from '@vueuse/core'
import { computed, ref } from "vue";
import { Spinner } from '@/Components/Spinner'

const emit = defineEmits<{
  (e: 'files', file: Array<File>): void
}>()
const props = withDefaults(defineProps<{
  multiple?: boolean
  allowed?: Array<string>
  processing?: boolean
  disabled?: boolean
  showIcon?: boolean
  dragLabel?: string
  pickLabel?: string
  orLabel?: string
}>(), {
  multiple: true,
  showIcon: true,
  dragLabel: 'Drag and drop a file',
  pickLabel: 'pick a file',
  orLabel: 'or',
})

const zoneEl = ref<HTMLDivElement>()
const inputEl = ref<HTMLInputElement>()

const onFiles = (files: Array<File> | null) => {
  if (props.disabled) {
    return
  }

  if (files && files.length > 0) {
    emit('files', props.multiple === true ? files : [files[0]])
  }
}

const { isOverDropZone } = useDropZone(zoneEl, {
  onDrop: onFiles,
  multiple: props.multiple,
  dataTypes: props.allowed,
})

const selectFile = () => {
  inputEl.value?.click()
}

const onInputChange = () => {
  const files = inputEl.value?.files
  if (files) {
    onFiles(Array.from(files))
  }
}

const accept = computed(() => props.allowed?.join(','))
</script>
