<template>
  <div
    ref="containerEl"
    :data-dragging="isDragging ? '' : undefined"
    :data-active="active ? '' : undefined"
    :class="cn(
      'relative flex flex-row transition items-start text-muted-foreground',
      'data-[dragging]:bg-primary/20 data-[dragging]:text-foreground',
      isDragging ? '' : 'data-[active]:bg-accent data-[active]:text-foreground not-data-[active]:hover:bg-accent/50',
    )"
  >
    <div
      ref="handleEl"
      :class="cn(
        'text-muted-foreground shrink-0 p-1.5 ml-[4px] mt-[8px] transition-colors cursor-move',
        disabled ? 'opacity-50 cursor-auto' : 'hover:text-foreground',
      )">
      <GripVerticalIcon class="size-4" />
    </div>

    <Link
      :href="route('studio.course.lessons.show', [courseId, lessonId])"
      preserve-scroll
      :class="cn('flex-1 flex px-1.5 py-3 cursor-pointer text-sm')"
    >
      {{ title }}
    </Link>

    <DropIndicator v-if="closestEdge" :edge="closestEdge" gap="1px" />
  </div>
</template>

<script setup lang="ts">
import { cn } from "@/Utils";
import type { CleanupFn } from "@atlaskit/pragmatic-drag-and-drop/types";
import { Link } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted, ref } from "vue";
import { draggable, dropTargetForElements } from '@atlaskit/pragmatic-drag-and-drop/element/adapter'
import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine'
import { attachClosestEdge, extractClosestEdge, type Edge } from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge'
import { GripVerticalIcon } from 'lucide-vue-next'
import DropIndicator from './DropIndicator.vue'

const props = defineProps<{
  courseId: string
  chapterId: string
  lessonId: string
  title: string
  active: boolean
  disabled?: boolean
}>()

const containerEl = ref<HTMLDivElement>()
const handleEl = ref<HTMLDivElement>()
const isDragging = ref(false)
const closestEdge = ref<Edge | null>()

let cleanup: CleanupFn | undefined

onMounted(() => {
  const container = containerEl.value
  const handle = handleEl.value

  if (container && handle) {
    cleanup = combine(
      draggable({
        element: container,
        dragHandle: handle,
        canDrag: () => !props.disabled,
        getInitialData: () => ({
          type: 'lesson',
          courseId: props.courseId,
          chapterId: props.chapterId,
          lessonId: props.lessonId,
        }),
        onDragStart: () => {
          isDragging.value = true
        },
        onDrop: () => {
          isDragging.value = false
        }
      }),

      dropTargetForElements({
        element: container,
        getIsSticky: () => true,
        getData: ({ input, element }) => {
          const data = { type: 'lesson', courseId: props.courseId, chapterId: props.chapterId, lessonId: props.lessonId }

          return attachClosestEdge(data, {
            input, element, allowedEdges: ['top', 'bottom'],
          })
        },
        canDrop: ({ source }) => {
          if (source.element === container) {
            return false
          }

          return source.data.type === 'lesson'
        },
        onDragEnter: ({ source, self }) => {
          if (source.data.lessonId !== props.lessonId) {
            closestEdge.value = extractClosestEdge(self.data)
          }
        },
        onDrag: ({ source, self }) => {
          if (source.data.lessonId !== props.lessonId) {
            closestEdge.value = extractClosestEdge(self.data)
          }
        },
        onDragLeave: () => {
          closestEdge.value = null
        },
        onDrop: () => {
          closestEdge.value = null
        }
      })
    )
  }
})

onBeforeUnmount(() => {
  if (cleanup) {
    cleanup()
    cleanup = undefined
  }
})
</script>
