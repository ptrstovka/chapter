<script setup lang="ts">
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { trans } from 'laravel-vue-i18n'
import { PencilIcon, Trash2Icon, UploadIcon } from 'lucide-vue-next'
import { Button } from '@/Components/Button'
import { Player } from '@/Components/Player'
import { Progress } from '@/Components/Progress'
import { Spinner } from '@/Components/Spinner'
import { useConfirmable } from '@/Components/ConfirmationDialog'
import type { AppPageProps, VideoSource } from '@/Types'
import { useVideoUploads, videoUploadError } from './manager'

const props = defineProps<{
  courseId: string
  lessonId?: string | null
  source: VideoSource | null
  label?: string
  disabled?: boolean
}>()
const uploads = useVideoUploads()
const page = usePage<AppPageProps>()
const input = ref<HTMLInputElement>()
const video = ref(props.source)
const error = ref('')
const selecting = ref(false)
const busy = ref(false)
const dragging = ref(false)
const task = computed(() => [...uploads.tasks].reverse().find(task => task.courseId === props.courseId && task.lessonId === (props.lessonId ?? null) && !['done', 'cancelled'].includes(task.status)))
const processing = computed(() => video.value && ['queued', 'processing'].includes(video.value.status))
let timer: ReturnType<typeof setInterval> | undefined
let refreshing = false
watch(() => props.source, value => { video.value = value })
watch(() => [props.courseId, props.lessonId], () => { video.value = props.source; void refresh() })

async function refresh() {
  if (refreshing) return
  refreshing = true
  const courseId = props.courseId
  const lessonId = props.lessonId ?? null
  try {
    const { data } = await axios.get<{ video: VideoSource | null }>(route('studio.videos', courseId), { params: { lesson_id: lessonId } })
    if (props.courseId === courseId && (props.lessonId ?? null) === lessonId) video.value = data.video
  } finally {
    refreshing = false
  }
}
const unsubscribe = uploads.subscribe(courseId => {
  if (courseId === props.courseId) void refresh().catch(() => undefined)
})
onMounted(() => {
  void refresh().catch(() => undefined)
  timer = setInterval(() => {
    if (processing.value || task.value) void refresh().catch(() => undefined)
  }, 5000)
})
onBeforeUnmount(() => { clearInterval(timer); unsubscribe() })

async function select(files: File[]) {
  if (props.disabled || selecting.value || !files.length) return
  error.value = ''
  const file = files[0]!
  const max = page.props.app.videoUploadMaxSize
  if (files.length !== 1 || !/\.(mp4|mov)$/i.test(file.name)) {
    error.value = trans('Select one MP4 or MOV video.')
    return
  }
  if (!file.size || file.size > max) {
    error.value = trans('The video must be between 1 byte and :max.', { max: page.props.app.videoUploadMaxSizeLabel })
    return
  }
  selecting.value = true
  try {
    await uploads.enqueue(file, props.courseId, props.lessonId ?? null, props.label ?? '')
  } catch (cause) {
    error.value = videoUploadError(cause)
  } finally {
    selecting.value = false
  }
}
function changed(event: Event) {
  const target = event.target as HTMLInputElement
  void select(Array.from(target.files ?? []))
  target.value = ''
}
function drop(event: DragEvent) {
  dragging.value = false
  void select(Array.from(event.dataTransfer?.files ?? []))
}
const { confirm } = useConfirmable()
function remove() {
  const target = video.value
  if (!target) return
  confirm(trans('Are you sure you want to delete this video?'), async () => {
    busy.value = true
    try {
      await axios.delete(route('studio.videos.destroy', [props.courseId, target.id]))
      await refresh()
    } catch (cause) {
      error.value = videoUploadError(cause)
    } finally {
      busy.value = false
    }
  }, { title: trans('Delete video'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
}
async function retryProcessing() {
  if (!video.value) return
  busy.value = true
  try {
    await axios.post(route('studio.videos.retry', [props.courseId, video.value.id]))
    await refresh()
  } catch (cause) {
    error.value = videoUploadError(cause)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="space-y-2">
    <input ref="input" type="file" accept=".mp4,.mov,video/mp4,video/quicktime" class="hidden" :disabled="disabled || selecting" :aria-label="$t('Select video')" @change="changed" />
    <div class="relative aspect-video overflow-hidden rounded-lg border bg-secondary/30" :class="dragging && !disabled ? 'border-primary ring-2 ring-ring' : ''" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop">
      <div v-if="task" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-10">
        <Spinner v-if="task.status !== 'failed'" class="size-6 text-muted-foreground" />
        <span class="max-w-full truncate text-sm">{{ task.name }}</span>
        <p class="text-center text-sm text-muted-foreground" role="status">
          {{ $t({ registering: 'Preparing upload', queued: 'Waiting for upload', uploading: 'Uploading', completing: 'Saving video', failed: 'Upload failed', done: 'Uploaded', cancelled: 'Cancelled' }[task.status]) }}
        </p>
        <Progress v-if="task.status === 'uploading'" :model-value="task.progress" class="max-w-xs" />
        <p v-if="task.status === 'failed'" class="text-center text-xs text-destructive" role="alert">{{ task.error }}</p>
        <div class="flex gap-2">
          <Button v-if="task.status === 'failed' && (task.file || task.received)" variant="outline" size="sm" @click="uploads.retry(task)">{{ $t('Retry upload') }}</Button>
          <Button variant="ghost" size="sm" @click="uploads.cancel(task)">{{ $t('Cancel upload') }}</Button>
        </div>
      </div>
      <div v-else-if="video?.status === 'failed' && !video.url" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-8">
        <p class="text-center text-sm text-destructive" role="alert">{{ $t('Video processing failed. Please try again.') }}</p>
        <Button variant="outline" size="sm" :processing="busy" :disabled="disabled" @click="retryProcessing">{{ $t('Retry processing') }}</Button>
      </div>
      <Player v-else-if="video" :src="video.url" :type="video.type" :poster="video.posterImageUrl" class="h-full w-full rounded-none" />
      <button v-else type="button" class="absolute inset-0 flex w-full flex-col items-center justify-center gap-2 text-sm text-muted-foreground disabled:opacity-50" :disabled="disabled || selecting" @click="input?.click()">
        <UploadIcon class="size-6" />
        <span>{{ $t('Drag & drop a video') }}</span>
        <span class="underline underline-offset-4">{{ $t('Select video') }}</span>
      </button>
      <div v-if="!disabled && (video || task)" class="absolute right-2 top-2 flex gap-1">
        <Button variant="outline" :icon="PencilIcon" class="size-8 bg-background p-1.5" :disabled="selecting || busy" :aria-label="$t('Replace video')" @click="input?.click()" />
        <Button v-if="video && !task" variant="outline" :icon="Trash2Icon" class="size-8 bg-background p-1.5 text-destructive" :disabled="busy" :aria-label="$t('Delete video')" @click="remove" />
      </div>
    </div>
    <p v-if="video?.status === 'failed' && video.url" class="text-sm text-destructive">{{ $t('Video processing failed. The previous stream is still available.') }}</p>
    <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>
  </div>
</template>
