<template>
  <Dropzone @files="onFiles" :class="cn('p-3 overflow-auto', $attrs.class || '')" :data-empty="fileArray.length === 0 ? '' : undefined">
    <div v-if="fileArray.length > 0" class="flex flex-col w-full h-full gap-2">
      <div v-for="fileItem in fileList" class="border h-16 px-3 pt-3 rounded-md flex flex-row gap-4">
        <FileTextIcon
          :class="cn('size-8 text-muted-foreground')"
        />

        <div class="flex flex-col flex-1 items-start gap-1">
          <p class="text-sm font-medium line-clamp-1">{{ fileItem.name }}</p>

          <div class="min-h-3 flex items-center w-full">
            <template v-if="fileItem.file || fileItem.pending?.isUploaded === true">
              <p class="text-xs leading-0 text-muted-foreground font-medium">{{ fileItem.size }}</p>
            </template>

            <template v-if="fileItem.pending && fileItem.pending.isUploaded === false">
              <p v-if="fileItem.pending.waitingForUpload" class="text-xs leading-0 text-muted-foreground">{{ $t('Pending') }}</p>
              <p v-else-if="fileItem.pending.uploadFailed" class="text-xs text-destructive">{{ fileItem.pending.failure || $t('Unable to upload this file') }}</p>
              <Progress v-else :model-value="fileItem.pending.progress" />
            </template>
          </div>
        </div>

        <div class="inline-flex flex-row items-start pt-2 gap-1">
          <template v-if="fileItem.pending">
            <Button v-if="fileItem.pending.uploadFailed" @click="retryUpload(fileItem)" variant="ghost" class="h-auto p-1">
              <RotateCcwIcon class="size-4" />
            </Button>
          </template>

          <Button @click="removeFile(fileItem)" variant="ghost-desctructive" class="h-auto p-1">
            <XIcon class="size-4" />
          </Button>
        </div>
      </div>
    </div>
  </Dropzone>
</template>

<script setup lang="ts">
import { cn, randomString } from "@/Utils";
import axios from "axios";
import { computed, markRaw, reactive, toRaw, watch } from "vue";
import type { FileListItem } from ".";
import { Dropzone } from '@/Components/Dropzone'
import { FileTextIcon, RotateCcwIcon, XIcon } from 'lucide-vue-next'
import { Progress } from '@/Components/Progress'
import { Button } from "@/Components/Button";
import isEqual from 'lodash/isEqual'

interface PendingUpload {
  progress: number
  waitingForUpload: boolean
  isUploading: boolean
  isUploaded: boolean
  uploadFailed: boolean
  file: File
  controller: AbortController | null
  failure: string | null
}

interface FileItem {
  id: string
  name: string
  mime: string
  size: string
  file: FileListItem | null
  pending: PendingUpload | null
}

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  scope: string
  modelValue?: Array<FileListItem>
}>()

const createFileItems: (files: Array<FileListItem>) => Record<string, FileItem> = files => {
  return files.map(it => ({
    id: randomString(),
    name: it.name,
    mime: it.mime,
    size: it.size,
    pending: null,
    file: it,
  })).reduce((acc: Record<string, FileItem>, val: FileItem) => {
    acc[val.id] = val
    return acc
  }, {})
}

const fileList = reactive<Record<string, FileItem>>(createFileItems(props.modelValue || []))
const fileArray = computed<Array<FileItem>>(() => Object.keys(fileList).map(key => fileList[key]))
const currentlyUploadingCount = computed(() => fileArray.value.filter(it => it.pending?.isUploading === true).length)

const onFiles = (files: Array<File>) => files.forEach(uploadFile)

const uploadFile = (file: File) => {
  const upload: FileItem = {
    id: randomString(),
    name: file.name,
    mime: file.type,
    size: `${file.size}`,
    file: null,
    pending: {
      progress: 0,
      waitingForUpload: false,
      isUploading: false,
      isUploaded: false,
      uploadFailed: false,
      file: markRaw(file),
      controller: null,
      failure: null,
    }
  }

  fileList[upload.id] = upload
  enqueueUpload(upload)
}

const enqueueUpload = async (f: FileItem) => {
  const file = fileList[f.id]

  if (! file.pending) {
    return
  }

  if (currentlyUploadingCount.value >= 3) {
    file.pending.waitingForUpload = true

    return
  }

  file.pending.waitingForUpload = false
  file.pending.isUploading = true
  file.pending.progress = 0
  file.pending.uploadFailed = false
  file.pending.failure = null
  file.pending.isUploaded = false

  const form = new FormData()
  form.append('file', file.pending.file)
  form.append('scope', props.scope)
  const controller = new AbortController()

  file.pending.controller = controller

  try {
    const response = await axios.post<{ id: string, url: string, name: string, mime: string, size: string }>(route('files.store'), form, {
      signal: controller.signal,
      onUploadProgress: event => {
        if (event.lengthComputable) {
          if (file.pending) {
            file.pending.progress = Math.round((event.loaded / (event.total || 1)) * 100)
          }
        }
      }
    })

    file.name = response.data.name
    file.mime = response.data.mime
    file.size = response.data.size
    file.pending.progress = 100
    file.pending.isUploaded = true
    file.file = {
      type: 'temporary',
      id: response.data.id,
      url: response.data.url,
      name: response.data.name,
      size: response.data.size,
      mime: response.data.mime,
    }
  } catch (error: any) {
    if (! axios.isCancel(error)) {
      file.pending.isUploaded = false
      file.pending.uploadFailed = true
      file.pending.failure = error.response?.data?.message || null
    }
  } finally {
    file.pending.controller = null
    file.pending.isUploading = false

    updateModelValue()

    enqueueNextPendingUpload()
  }
}

const enqueueNextPendingUpload = () => {
  let pending = fileArray.value.find(it => it.pending?.waitingForUpload === true)

  if (pending) {
    enqueueUpload(pending)
  }
}

const retryUpload = (file: FileItem) => {
  if (! file.pending) {
    return
  }

  if (file.pending.uploadFailed) {
    file.pending.uploadFailed = false
    file.pending.failure = null
    enqueueUpload(file)
  }
}

const removeFile = (file: FileItem) => {
  if (file.pending) {
    file.pending.controller?.abort()
    file.pending.controller = null
  }

  delete fileList[file.id]

  updateModelValue()
}

const updateModelValue = () => {
  const currentModelValue = toRaw(props.modelValue || [])
  const internalValue: Array<FileListItem> = fileArray.value
    .map(it => it.file)
    .filter(it => !!it)
    .map(it => toRaw(it))

  if (! isEqual(currentModelValue, internalValue)) {
    emit('update:modelValue', internalValue)
  }
}

watch(() => props.modelValue, updatedModelValue => {
  const updated: Array<FileListItem> = toRaw(updatedModelValue || [])
  const current: Array<FileListItem> = fileArray.value
    .map(it => it.file)
    .filter(it => !!it)
    .map(it => toRaw(it))

  if (! isEqual(current, updated)) {
    // TODO: ponechať poradie

    const newList: Array<FileItem> = updated.map(it => ({
      id: randomString(),
      file: it,
      pending: null,
      name: it.name,
      mime: it.mime,
      size: it.size
    }))

    fileArray.value.filter(it => it.pending && !it.file).forEach(it => {
      newList.push(it)
    })

    Object.keys(fileList).forEach(key => {
      delete fileList[key]
    })

    newList.forEach(file => {
      fileList[file.id] = file
    })
  }
})
</script>
