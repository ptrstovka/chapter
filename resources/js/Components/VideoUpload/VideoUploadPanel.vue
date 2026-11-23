<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { XIcon } from 'lucide-vue-next'
import { Button } from '@/Components/Button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/Collapsible'
import { Progress } from '@/Components/Progress'
import type { AppPageProps } from '@/Types'
import { useVideoUploads } from './manager'

const page = usePage<AppPageProps>()
const uploads = useVideoUploads()
const { batchTasks, active, open, visible, toggle, dismiss, retry, cancel } = uploads
const canUpload = computed(() => page.props.auth?.user?.can?.accessStudio)
const completed = computed(() => batchTasks.value.filter((task) => task.status === 'done').length)
const uploading = computed(() => batchTasks.value.some((task) => ['queued', 'registering', 'uploading', 'completing'].includes(task.status)))
const progress = computed(() => {
  const total = batchTasks.value.reduce((bytes, task) => bytes + task.size, 0)
  return total ? Math.round(batchTasks.value.reduce((bytes, task) => bytes + task.size * task.progress, 0) / total) : 0
})
const warning = (event: BeforeUnloadEvent) => {
  if (active.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}
let timer: ReturnType<typeof setInterval> | undefined
watch(() => page.props.auth?.user?.id, () => uploads.clear())
onMounted(() => {
  timer = setInterval(() => {
    if (canUpload.value && active.value) void uploads.refresh().catch(() => undefined)
  }, 5000)
  window.addEventListener('beforeunload', warning)
})
onBeforeUnmount(() => {
  clearInterval(timer)
  window.removeEventListener('beforeunload', warning)
})
</script>

<template>
  <Collapsible v-if="visible" :open="open" as-child @update:open="toggle">
    <aside
      :aria-label="$t('Video uploads')"
      class="fixed bottom-4 right-4 z-40 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border bg-background shadow-lg in-[body:has([data-studio-save-footer])]:bottom-20"
    >
      <div class="relative">
        <CollapsibleTrigger as-child>
          <Button
            plain
            variant="ghost"
            class="h-auto w-full justify-start gap-3 rounded-none py-2.5 pl-3 pr-10"
          >
            <span class="relative flex size-9 shrink-0 items-center justify-center">
              <svg viewBox="0 0 36 36" class="absolute inset-0 size-9 -rotate-90" aria-hidden="true">
                <circle cx="18" cy="18" r="16" fill="none" stroke-width="4" class="stroke-current text-primary/20" />
                <circle
                  cx="18"
                  cy="18"
                  r="16"
                  fill="none"
                  stroke-width="4"
                  stroke-linecap="round"
                  pathLength="100"
                  stroke-dasharray="100"
                  :stroke-dashoffset="100 - progress"
                  class="stroke-current text-primary transition-[stroke-dashoffset]"
                />
              </svg>
              <span class="text-[10px] font-medium tabular-nums">{{ progress }}</span>
            </span>
            <span>{{ $t(!active ? 'Uploaded' : uploading ? 'Uploading…' : 'Upload failed') }} ({{ completed }}/{{ batchTasks.length }})</span>
          </Button>
        </CollapsibleTrigger>
        <Button
          v-if="!active"
          variant="ghost"
          size="icon"
          class="absolute right-3 top-1/2 size-5 -translate-y-1/2 text-muted-foreground"
          :aria-label="$t('Close uploads')"
          @click="dismiss"
        ><XIcon class="size-3.5" /></Button>
      </div>
      <CollapsibleContent
        :inert="!open"
        class="overflow-hidden data-[state=open]:animate-collapsible-down data-[state=closed]:animate-collapsible-up motion-reduce:animate-none"
      >
        <div class="max-h-96 divide-y overflow-y-auto border-t">
          <div v-for="task in batchTasks" :key="task.id" class="space-y-2 p-3">
            <div class="flex items-center justify-between gap-3">
              <p class="min-w-0 truncate text-sm font-medium">{{ task.name }}</p>
              <span
                v-if="['uploading', 'completing'].includes(task.status) && task.remainingSeconds !== undefined"
                class="shrink-0 text-xs tabular-nums text-muted-foreground"
                :aria-label="$t('Estimated time remaining: :seconds seconds', { seconds: String(task.remainingSeconds) })"
              >{{ Math.floor(task.remainingSeconds / 60) }}m {{ task.remainingSeconds % 60 }}s</span>
            </div>
            <p v-if="task.status === 'done'" class="text-xs text-muted-foreground">{{ $t('Uploaded') }}</p>
            <template v-else>
              <div class="flex items-center gap-2">
                <Progress class="h-1 flex-1" :model-value="task.progress" :aria-label="$t('Progress: :name', { name: task.name })" />
                <span class="text-xs tabular-nums text-muted-foreground">{{ task.progress }}</span>
                <Button
                  variant="ghost"
                  size="icon"
                  class="size-5 shrink-0 text-muted-foreground"
                  :aria-label="$t('Cancel upload: :name', { name: task.name })"
                  @click="cancel(task)"
                ><XIcon class="size-3.5" /></Button>
              </div>
              <template v-if="task.status === 'failed'">
                <p v-if="task.error" class="text-xs text-destructive" role="alert">{{ task.error }}</p>
                <Button variant="outline" size="default" class="h-7 rounded px-2" @click="retry(task)">{{ $t('Retry') }}</Button>
              </template>
            </template>
          </div>
        </div>
      </CollapsibleContent>
    </aside>
  </Collapsible>
</template>
