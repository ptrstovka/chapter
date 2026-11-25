<template>
  <CourseLayout :title="$t('Content')" :editor="editor">
    <div class="relative h-[calc(100vh_-_8.5rem)] overflow-hidden flex flex-row">
      <div v-if="chapters.length > 0" class="w-80 shrink-0 overflow-y-auto [scrollbar-width:thin] border-r" scroll-region="">
        <div :class="cn('flex flex-col', { 'divide-y border-b border-dashed': page.props.isEditable })">
          <CourseChapter
            v-for="(chapter, idx) in chapters"
            :title="chapter.title || chapter.fallbackTitle"
            :chapter-id="chapter.id"
            :course-id="page.props.id"
            :active="route().current(courseRouteName('course.chapters.show'), [page.props.id, chapter.id])"
            :first="idx === 0"
            :disabled="!canEditOutline"
          >
            <div v-if="chapter.lessons.length > 0" class="divide-y border-b">
              <CourseLesson
                v-for="lesson in chapter.lessons"
                :course-id="page.props.id"
                :chapter-id="chapter.id"
                :lesson-id="lesson.id"
                :title="lesson.title"
                :active="route().current(courseRouteName('course.lessons.show'), [page.props.id, lesson.id])"
                :disabled="!canEditOutline"
              />
            </div>
          </CourseChapter>
        </div>

        <div class="p-3" v-if="canEditOutline">
          <Button
            class="w-full"
            @click="createChapter"
            :processing="createChapterForm.processing"
            variant="outline"
            :label="$t('New Chapter')"
            :icon="PlusIcon"
          />
        </div>
      </div>

      <div class="flex-1 flex flex-col">
        <div class="flex-1 overflow-y-auto  [scrollbar-width:thin]">
          <slot />
        </div>

        <div v-if="$slots.footer" data-studio-save-footer class="h-12 w-full bg-secondary/30 flex px-3 items-center border-t">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </CourseLayout>
</template>

<script setup lang="ts">
import { useCourseEditorRoutes } from '@/Composables/useCourseEditorRoutes'
import { Button } from "@/Components/Button";
import type { AppPageProps } from "@/Types";
import { cn } from "@/Utils";
import { extractClosestEdge } from "@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge";
import { monitorForElements } from "@atlaskit/pragmatic-drag-and-drop/element/adapter";
import { getReorderDestinationIndex } from '@atlaskit/pragmatic-drag-and-drop-hitbox/util/get-reorder-destination-index';
import { reorder } from '@atlaskit/pragmatic-drag-and-drop/reorder';
import { combine } from "@atlaskit/pragmatic-drag-and-drop/combine";
import type { CleanupFn } from "@atlaskit/pragmatic-drag-and-drop/types";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import CourseLayout from "./CourseLayout.vue";
import { PlusIcon } from 'lucide-vue-next'
import CourseChapter from '../Components/CourseChapter.vue'
import CourseLesson from '../Components/CourseLesson.vue'
import type { CourseEditorForm } from '../useCourseEditorForm'

const { courseRouteName } = useCourseEditorRoutes()

const props = defineProps<{ editor?: CourseEditorForm }>()

interface Lesson {
  id: string
  title: string
}

interface Chapter {
  id: string
  position: number
  title: string | null
  fallbackTitle: string
  lessons: Array<Lesson>
}

const page = usePage<AppPageProps & {
  id: string
  chapters: Array<Chapter>
  isEditable: boolean
  revision: number
}>()

const copyOutline = () => page.props.chapters.map(chapter => ({ ...chapter, lessons: chapter.lessons.map(lesson => ({ ...lesson })) }))
const chapters = ref(copyOutline())
const canEditOutline = computed(() => page.props.isEditable && !page.props.errors.revision && !props.editor?.processing)

const createChapterForm = useForm({})
const createChapter = () => {
  createChapterForm.transform(() => ({ revision: page.props.revision })).post(route(courseRouteName('course.chapters.store'), page.props.id), {
    preserveScroll: true,
  })
}

let cleanup: CleanupFn | undefined

onMounted(() => {
  cleanup = combine(
    monitorForElements({
      canMonitor: ({ source }) => {
        return canEditOutline.value && (source.data.type === 'lesson' || source.data.type === 'chapter')
      },
      onDrop: ({ location, source }) => {
        if (!location.current.dropTargets.length) {
          return
        }

        if (source.data.type === 'chapter') {
          const startIndex = chapters.value.findIndex(it => it.id === source.data.chapterId)
          const target = location.current.dropTargets[0]
          const indexOfTarget = chapters.value.findIndex(it => it.id === target.data.chapterId)

          const closestEdgeOfTarget = extractClosestEdge(target.data)

          const finishIndex = getReorderDestinationIndex({
            startIndex, indexOfTarget, closestEdgeOfTarget, axis: 'vertical'
          })

          moveChapter(startIndex, finishIndex)
        }

        if (source.data.type === 'lesson') {
          const sourceChapter = chapters.value.find(it => it.id === source.data.chapterId)!
          const itemIndex = sourceChapter.lessons.findIndex(it => it.id === source.data.lessonId)
          const target = location.current.dropTargets[0]
          const destinationChapter = chapters.value.find(it => it.id === target.data.chapterId)!
          const indexOfTarget = destinationChapter.lessons.findIndex(it => it.id === target.data.lessonId)
          const closestEdgeOfTarget = extractClosestEdge(target.data)

          if (sourceChapter.id === destinationChapter.id) {
            const destinationIndex = getReorderDestinationIndex({
              startIndex: itemIndex,
              indexOfTarget,
              closestEdgeOfTarget,
              axis: 'vertical'
            })

            moveLessonWithinChapter(destinationChapter.id, itemIndex, destinationIndex)
          } else {
            const destinationIndex = indexOfTarget < 0 ? 0 : (closestEdgeOfTarget === 'bottom' ? indexOfTarget + 1 : indexOfTarget)

            moveLessonToChapter(sourceChapter.id, destinationChapter.id, itemIndex, destinationIndex)
          }
        }
      }
    })
  )
})

onBeforeUnmount(() => {
  if (cleanup) {
    cleanup()
    cleanup = undefined
  }
})

const moveChapter = (startIndex: number, finishIndex: number) => {
  chapters.value = reorder({
    list: chapters.value,
    startIndex,
    finishIndex,
  })

  updateFallbackTitles()

  router.post(route(courseRouteName('course.chapters.sort'), page.props.id), {
    chapters: chapters.value.map(it => it.id),
    revision: page.props.revision,
  }, {
    preserveScroll: true,
  })
}

const moveLessonWithinChapter = (chapterId: string, startIndex: number, finishIndex: number) => {
  const chapter = chapters.value.find(it => it.id === chapterId)!
  const lesson = chapter.lessons[startIndex]

  chapters.value = chapters.value.map(chapter => {
    if (chapter.id === chapterId) {
      chapter.lessons = reorder({
        list: chapter.lessons,
        startIndex,
        finishIndex
      })
    }

    return chapter
  })

  updateFallbackTitles()

  router.post(route(courseRouteName('course.lessons.move'), page.props.id), {
    source_chapter: chapter.id,
    destination_chapter: chapter.id,
    lesson: lesson.id,
    destination_index: finishIndex,
    revision: page.props.revision,
  }, {
    preserveScroll: true,
  })
}

const moveLessonToChapter = (sourceChapterId: string, destinationChapterId: string, startIndex: number, finishIndex: number) => {
  const lesson = chapters.value.find(it => it.id === sourceChapterId)!.lessons[startIndex]

  chapters.value = chapters.value.map(chapter => {
    if (chapter.id === sourceChapterId) {
      chapter.lessons = chapter.lessons.filter((_, idx) => idx !== startIndex)
    } else if (chapter.id === destinationChapterId) {
      const updatedLessons = [...chapter.lessons]
      updatedLessons.splice(finishIndex, 0, lesson)
      chapter.lessons = updatedLessons
    }

    return chapter
  })

  updateFallbackTitles()

  router.post(route(courseRouteName('course.lessons.move'), page.props.id), {
    source_chapter: sourceChapterId,
    destination_chapter: destinationChapterId,
    lesson: lesson.id,
    destination_index: finishIndex,
    revision: page.props.revision,
  }, {
    preserveScroll: true,
  })
}

const updateFallbackTitles = () => {
  chapters.value = chapters.value.map((chapter, chapterIndex) => {
    chapter.fallbackTitle = trans('Chapter :value', { value: `${chapterIndex + 1}` })

    return chapter
  })
}

watch(() => page.props.chapters, () => {
  chapters.value = copyOutline()
})
</script>
