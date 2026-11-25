import axios from 'axios'
import type { AppPageProps } from '@/Types'
import { router, usePage } from '@inertiajs/vue3'
import { computed, markRaw, reactive, ref } from 'vue'
import { trans } from 'laravel-vue-i18n'

interface UploadReceipt {
  id: string
  courseId: string
  lessonId: string | null
  name: string
  size: number
  status: 'pending' | 'completed' | 'cancelled'
  received: boolean
  videoId: number | null
  editorRevision: EditorRevision | null
}

export interface EditorRevision {
  before: number
  after: number
}

export function advanceCourseRevision(courseId: string, revision: EditorRevision | null | undefined) {
  const page = usePage<AppPageProps & { id?: string; revision?: number }>()
  if (revision && page.props.id === courseId && page.props.revision === revision.before && !page.props.errors.revision) {
    router.replaceProp('revision', revision.after)
  }
}

export interface VideoUploadTask {
  id: string
  batchId: number
  courseId: string
  lessonId: string | null
  label: string
  name: string
  size: number
  revision: number | undefined
  file?: File
  registered: boolean
  received: boolean
  status: 'registering' | 'queued' | 'uploading' | 'completing' | 'done' | 'failed' | 'cancelled'
  progress: number
  remainingSeconds?: number
  error: string
  controller?: AbortController
}

const tasks = reactive<VideoUploadTask[]>([])
const open = ref(false)
const hasOpened = ref(false)
const batchId = ref(0)
const listeners = new Set<(courseId: string) => void>()
const registrations = new Map<string, Promise<void>>()
let running = false
let refreshing = false
const pending = (task: VideoUploadTask) => !['done', 'cancelled'].includes(task.status)
const batchTasks = computed(() => tasks.filter(task => task.batchId === batchId.value && task.status !== 'cancelled'))
const active = computed(() => tasks.some(pending))
const visible = computed(() => active.value || (hasOpened.value && batchTasks.value.length > 0))

function notify(courseId: string) {
  listeners.forEach(listener => listener(courseId))
}

export function videoUploadError(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message || trans('The request failed. Please try again.')
  }
  return error instanceof Error ? error.message : trans('The request failed. Please try again.')
}

async function register(task: VideoUploadTask) {
  const { data } = await axios.post<UploadReceipt>(route('studio.video-uploads.store', task.courseId), {
    uuid: task.id, lesson_id: task.lessonId, filename: task.name, size: task.size, revision: task.revision,
  })
  task.registered = true
  task.received = data.received
  if (task.status === 'cancelled') {
    await axios.delete(route('studio.video-uploads.destroy', [task.courseId, task.id]), { data: { lesson_id: task.lessonId, filename: task.name, size: task.size } })
    return
  }
  task.status = data.status === 'completed' ? 'done' : data.status === 'cancelled' ? 'cancelled' : 'queued'
  advanceCourseRevision(task.courseId, data.editorRevision)
  notify(task.courseId)
}

async function processQueue() {
  if (running) return
  running = true
  try {
    while (true) {
      const task = tasks.find(item => ['registering', 'queued'].includes(item.status))
      if (!task || task.status === 'registering') break
      task.error = ''
      task.controller = markRaw(new AbortController())
      const signal = task.controller.signal
      try {
        const { data } = await axios.get<UploadReceipt>(route('studio.video-uploads.show', task.id), { signal })
        task.received = data.received
        if (data.status === 'cancelled') {
          task.status = 'cancelled'
          continue
        }
        if (data.status !== 'completed') {
          if (!task.received) {
            if (!task.file) throw new Error(trans('Select the file again to restart this upload.'))
            task.status = 'uploading'
            const form = new FormData()
            form.set('scope', task.lessonId ? 'CourseVideo' : 'CourseTrailerVideo')
            form.set('video_upload_id', task.id)
            form.set('file', task.file)
            const started = performance.now()
            await axios.post(route('files.store'), form, {
              signal,
              onUploadProgress: event => {
                if (!event.total) return
                task.progress = Math.min(100, Math.round(event.loaded / event.total * 100))
                const elapsed = (performance.now() - started) / 1000
                if (event.loaded > 0 && elapsed >= 1) {
                  task.remainingSeconds = Math.ceil((event.total - event.loaded) * elapsed / event.loaded)
                }
              },
            })
            task.received = true
          }
          signal.throwIfAborted()
          task.status = 'completing'
          task.progress = 100
          const result = await axios.post<UploadReceipt>(route('studio.video-uploads.complete', task.id), {}, { signal })
          advanceCourseRevision(task.courseId, result.data.editorRevision)
        } else {
          advanceCourseRevision(task.courseId, data.editorRevision)
        }
        task.status = 'done'
        task.progress = 100
        task.file = undefined
        notify(task.courseId)
      } catch (error) {
        if (!signal.aborted) {
          task.status = 'failed'
          task.error = videoUploadError(error)
          notify(task.courseId)
        }
      } finally {
        task.controller = undefined
        task.remainingSeconds = undefined
      }
    }
  } finally {
    running = false
  }
}

async function cancel(task: VideoUploadTask) {
  task.status = 'cancelled'
  task.controller?.abort()
  task.remainingSeconds = undefined
  notify(task.courseId)
  try {
    await registrations.get(task.id)?.catch(() => undefined)
    const { data } = await axios.delete<UploadReceipt>(route('studio.video-uploads.destroy', [task.courseId, task.id]), { data: { lesson_id: task.lessonId, filename: task.name, size: task.size } })
    if (data.status === 'completed') {
      task.status = 'done'
      task.progress = 100
    }
    task.file = undefined
  } catch (error) {
    if (!axios.isAxiosError(error) || error.response?.status !== 404) {
      task.status = 'failed'
      task.error = videoUploadError(error)
    }
  }
  notify(task.courseId)
  void processQueue()
}

async function retry(task: VideoUploadTask) {
  if (task.status !== 'failed') return
  task.error = ''
  try {
    if (!task.registered) {
      task.status = 'registering'
      const registration = register(task)
      registrations.set(task.id, registration)
      await registration
      registrations.delete(task.id)
    } else {
      task.status = 'queued'
    }
    task.progress = task.received ? 100 : 0
    void processQueue()
  } catch (error) {
    task.status = 'failed'
    task.error = videoUploadError(error)
  }
}

async function refresh() {
  if (refreshing) return
  refreshing = true
  try {
    const localTasks = tasks.filter(task => pending(task) && task.file)
    const { data } = await axios.get<{ uploads: UploadReceipt[] }>(route('studio.video-uploads'), {
      params: { active: localTasks.map(task => task.id) },
    })
    for (const receipt of data.uploads) {
      const task = tasks.find(item => item.id === receipt.id)
      if (task && pending(task)) {
        task.registered = true
        task.received = receipt.received
        if (receipt.status !== 'pending') {
          task.controller?.abort()
          task.status = receipt.status === 'completed' ? 'done' : 'cancelled'
          advanceCourseRevision(task.courseId, receipt.editorRevision)
          if (task.status === 'done') task.progress = 100
          task.file = undefined
          notify(task.courseId)
        }
      }
    }
  } finally {
    refreshing = false
  }
}

export function useVideoUploads() {
  return {
    tasks, batchTasks, active, visible, open,
    toggle() {
      open.value = !open.value
      if (open.value) hasOpened.value = true
    },
    hasPending: (courseId: string) => tasks.some(task => task.courseId === courseId && pending(task)),
    async enqueue(file: File, courseId: string, lessonId: string | null, label: string, revision?: number) {
      const previous = tasks.filter(task => task.courseId === courseId && task.lessonId === lessonId && pending(task))
      for (const task of previous) await cancel(task)
      if (!active.value) {
        batchId.value++
        open.value = false
        hasOpened.value = false
      }
      const task = reactive<VideoUploadTask>({
        id: crypto.randomUUID(), batchId: batchId.value, courseId, lessonId, label, name: file.name, size: file.size,
        revision, file: markRaw(file), registered: false, received: false, status: 'registering', progress: 0, error: '',
      })
      tasks.push(task)
      notify(courseId)
      const registration = register(task)
      registrations.set(task.id, registration)
      try {
        await registration
      } catch (error) {
        if (task.status !== 'cancelled') {
          task.status = 'failed'
          task.error = videoUploadError(error)
        }
      } finally {
        registrations.delete(task.id)
        void processQueue()
      }
    },
    subscribe(listener: (courseId: string) => void) {
      listeners.add(listener)
      return () => { listeners.delete(listener) }
    },
    dismiss() {
      open.value = false
      hasOpened.value = false
    },
    clear() {
      tasks.forEach(task => task.controller?.abort())
      tasks.splice(0)
      open.value = false
      hasOpened.value = false
    },
    cancel, retry, refresh,
  }
}
