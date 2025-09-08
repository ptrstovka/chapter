<template>
  <FormControl class="group/temporary-file-input" :error="error || errorMessage" :data-preview="(preview && !props.remove) ? '' : undefined">
    <Dropzone
      v-if="showDropZone"
      :class="cn($attrs.class || '')"
      @files="onFiles"
      :processing="uploading"
      :disabled="disabled"
      :show-icon="showIcon"
      :drag-label="dragLabel"
      :pick-label="pickLabel"
      :or-label="orLabel"
    />

    <div v-else :class="cn('relative shadow-xs border border-input rounded-md overflow-hidden', $attrs.class || '')">
      <slot v-if="preview" v-bind="{ preview }" />

      <TooltipProvider :delay-duration="0" v-if="! disabled">
        <Tooltip @click.stop>
          <TooltipTrigger as-child>
            <Button variant="ghost" class="text-destructive hover:bg-destructive/10 hover:text-destructive absolute top-1 right-1 h-auto p-1.5" @click="remove">
              <Trash2Icon class="size-4" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Delete') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>
  </FormControl>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { Dropzone } from '@/Components/Dropzone'
import { FormControl } from "@/Components/Form";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/Tooltip";
import { cn } from "@/Utils";
import axios from 'axios'
import { Trash2Icon } from "lucide-vue-next";
import { computed, ref, watch } from "vue";

const emit = defineEmits(['update:file', 'update:remove'])

const props = withDefaults(defineProps<{
  error?: string | null | undefined
  scope: string
  source: string | null
  remove: boolean
  file: string | null
  disabled?: boolean
  showIcon?: boolean
  dragLabel?: string
  pickLabel?: string
  orLabel?: string
}>(), {
  showIcon: true,
})

interface Upload {
  id: string
  url: string
}

const uploading = ref(false)
const uploadedFile = ref<Upload>()
const errorMessage = ref<string>()

const hasOriginalFile = computed(() => !!props.source)
const hasPendingUpload = computed(() => !!uploadedFile.value)

const preview = computed(() => uploadedFile.value?.url || props.source)

const showDropZone = computed(() => {
  if (uploading.value || props.remove) {
    return true
  }

  if (hasOriginalFile.value || hasPendingUpload.value) {
    return false
  }

  return true
})

const onFiles = async (files: Array<File>) => {
  await upload(files[0])
}

const upload = async (file: File) => {
  const shouldRemove = props.remove

  emit('update:remove', false)
  emit('update:file', null)

  uploading.value = true
  uploadedFile.value = undefined
  errorMessage.value = undefined

  const formData = new FormData()
  formData.append('scope', props.scope)
  formData.append('file', file)

  try {
    const response = await axios.post<Upload>(route('files.store'), formData)

    uploadedFile.value = response.data

    onNewFileUploaded(response.data)
  } catch (e: any) {
    const message = e.response?.data?.message

    if (message) {
      errorMessage.value = message
    } else {
      errorMessage.value = 'Súbor sa nepodarilo nahrať.'
    }

    if (shouldRemove) {
      emit('update:remove', true)
    }
  }

  uploading.value = false
}

const onNewFileUploaded = (upload: Upload) => {
  emit('update:remove', false)
  emit('update:file', upload.id)
}

const remove = () => {
  if (hasOriginalFile.value) {
    emit('update:remove', true)
    emit('update:file', null)
  } else {
    emit('update:remove', false)
    emit('update:file', null)
  }

  uploadedFile.value = undefined
}

watch(hasOriginalFile, (value, oldValue) => {
  if (oldValue === false && value === true) {
    emit('update:file', null)
    emit('update:remove', false)
    uploadedFile.value = undefined
  }
})
</script>
