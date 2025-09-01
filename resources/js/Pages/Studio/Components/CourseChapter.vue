<template>
  <div ref="containerEl" class="relative">
    <div ref="containerInnerEl" :class="cn(
      'transition-colors',
      { 'bg-primary/10': isDraggedInto }
    )">
      <div :class="cn(
        'relative flex flex-row transition items-start border-b',
        isDraggedInto ? '' : (active ? 'bg-accent' : 'hover:bg-accent/50'),
      )">
        <div
          ref="handleEl"
          :class="cn(
            'text-muted-foreground shrink-0 p-1.5 ml-[4px] mt-[8px] transition-colors cursor-move',
            disabled ? 'opacity-50 cursor-auto' : 'hover:text-foreground',
          )">
          <GripVerticalIcon class="size-4" />
        </div>

        <PlaceholderPattern class="opacity-80 pointer-events-none" />

        <Link
          :href="route('studio.course.chapters.show', [courseId, chapterId])"
          :class="cn('flex-1 px-1.5 py-3 cursor-pointer text-sm font-medium')"
          preserve-scroll
        >
          {{ title }}
        </Link>
      </div>

      <slot />

      <div class="px-4 py-2" v-if="!disabled">
        <Button
          class="w-full"
          variant="ghost"
          :label="$t('Add Lesson')"
          :icon="PlusIcon"
          @click="createLesson"
          :processing="createLessonForm.processing"
        />
      </div>
    </div>

    <DropIndicator v-if="closestEdge" :edge="closestEdge" :gap="gap" />
  </div>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { PlaceholderPattern } from "@/Components/Skeleton";
import { cn } from "@/Utils";
import DropIndicator from './DropIndicator.vue'
import {
  attachClosestEdge,
  type Edge,
  extractClosestEdge
} from "@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge";
import { combine } from "@atlaskit/pragmatic-drag-and-drop/combine";
import { draggable, dropTargetForElements } from "@atlaskit/pragmatic-drag-and-drop/element/adapter";
import type { CleanupFn } from "@atlaskit/pragmatic-drag-and-drop/types";
import { Link, useForm } from "@inertiajs/vue3";
import { GripVerticalIcon, PlusIcon } from "lucide-vue-next";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps<{
  courseId: string
  chapterId: string
  title: string
  active: boolean
  first: boolean
  disabled?: boolean
}>()

const createLessonForm = useForm({})
const createLesson = () => {
  createLessonForm.post(route('studio.course.lessons.store', [props.courseId, props.chapterId]), {
    preserveScroll: true,
  })
}

const containerEl = ref<HTMLDivElement>()
const containerInnerEl = ref<HTMLDivElement>()
const handleEl = ref<HTMLDivElement>()
let cleanup: CleanupFn | undefined

const isDragging = ref(false)
const closestEdge = ref<Edge | null>()
const isDraggedInto = ref(false)

const gap = computed(() => {
  if (props.first && closestEdge.value === 'top') {
    return '-4px'
  }

  return '2px'
})

onMounted(() => {
  const container = containerEl.value
  const containerInner = containerInnerEl.value
  const handle = handleEl.value

  if (container && containerInner && handle) {
    cleanup = combine(
      draggable({
        element: container,
        dragHandle: handle,
        canDrag: () => !props.disabled,
        getInitialData: () => ({
          type: 'chapter',
          courseId: props.courseId,
          chapterId: props.chapterId,
        }),
        onDragStart: () => {
          isDragging.value = true
        },
        onDrop: () => {
          isDragging.value = false
        }
      }),

      dropTargetForElements({
        element: containerInner,
        getData: () => ({ type: 'chapter', courseId: props.courseId, chapterId: props.chapterId }),
        canDrop: ({ source }) => source.data.type === 'lesson',
        getIsSticky: () => true,
        onDragEnter: () => {
          isDraggedInto.value = true
        },
        onDragStart: () => {
          isDraggedInto.value = true
        },
        onDragLeave: () => {
          isDraggedInto.value = false
        },
        onDrop: () => {
          isDraggedInto.value = false
        }
      }),

      dropTargetForElements({
        element: container,
        canDrop: ({ source }) => {
          if (source.element === container) {
            return false
          }

          return source.data.type === 'chapter'
        },
        getIsSticky: () => true,
        getData: ({ input, element }) => {
          const data = { type: 'chapter', courseId: props.courseId, chapterId: props.chapterId }

          return attachClosestEdge(data, {
            input, element, allowedEdges: ['top', 'bottom'],
          })
        },
        onDragEnter: ({ source, self }) => {
          if (source.data.chapterId !== props.chapterId) {
            closestEdge.value = extractClosestEdge(self.data)
          }
        },
        onDrag: ({ source, self }) => {
          if (source.data.chapterId !== props.chapterId) {
            closestEdge.value = extractClosestEdge(self.data)
          }
        },
        onDragLeave: () => {
          closestEdge.value = null
        },
        onDrop: () => {
          closestEdge.value = null
        }
      }),
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
