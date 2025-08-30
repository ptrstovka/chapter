<template>
  <CourseLayout :title="$t('Content')">
    <div class="relative h-[calc(100vh_-_8.5rem)] overflow-hidden flex flex-row">
      <div v-if="page.props.chapters.length > 0" class="w-80 shrink-0 overflow-y-auto [scrollbar-width:thin] border-r" scroll-region="">
        <div class="flex flex-col divide-y border-b border-dashed">
          <CourseChapter
            v-for="chapter in page.props.chapters"
            :title="chapter.title || chapter.fallbackTitle"
            :chapter-id="chapter.id"
            :course-id="page.props.id"
            :active="route().current('studio.course.chapters.show', [page.props.id, chapter.id])"
          >
            <div v-if="chapter.lessons.length > 0" class="divide-y border-b">
              <CourseLesson
                v-for="lesson in chapter.lessons"
                :course-id="page.props.id"
                :chapter-id="chapter.id"
                :lesson-id="lesson.id"
                :title="lesson.title || lesson.fallbackTitle"
                :active="route().current('studio.course.lessons.show', [page.props.id, chapter.id, lesson.id])"
              />
            </div>
          </CourseChapter>
        </div>

        <div class="p-3">
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

        <div v-if="$slots.footer" class="h-12 w-full flex px-3 items-center border-t">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </CourseLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import type { AppPageProps } from "@/Types";
import { useForm, usePage } from "@inertiajs/vue3";
import CourseLayout from "./CourseLayout.vue";
import { PlusIcon } from 'lucide-vue-next'
import CourseChapter from '../Components/CourseChapter.vue'
import CourseLesson from '../Components/CourseLesson.vue'

const page = usePage<AppPageProps & {
  id: string
  chapters: Array<{
    id: string
    position: number
    title: string | null
    fallbackTitle: string
    lessons: Array<{
      id: string
      title: string | null
      fallbackTitle: string
    }>
  }>
}>()

const createChapterForm = useForm({})
const createChapter = () => {
  createChapterForm.post(route('studio.course.chapters.store', page.props.id), {
    preserveScroll: true,
  })
}
</script>
