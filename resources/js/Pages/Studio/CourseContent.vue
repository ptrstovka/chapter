<template>
  <CourseContentLayout>
    <div v-if="chapters.length === 0" class="items-center flex flex-col">
      <EmptyState
        class="pt-20 w-full"
        :title="$t('This course does not have any lessons.')"
        :message="$t('Get started by adding first lesson.')"
      >
        <Button
          v-if="isEditable && !page.props.errors.revision"
          class="mt-4"
          :icon="PlusIcon"
          :label="$t('Create Lesson')"
          :processing="createChapterForm.processing"
          @click="createChapter"
        />
      </EmptyState>
    </div>

    <div v-else class="flex flex-1 flex-row justify-center items-center h-full">
      <p class="text-sm text-muted-foreground">{{ $t('Select chapter or lesson to edit') }}</p>
    </div>
  </CourseContentLayout>
</template>

<script setup lang="ts">
import { useCourseEditorRoutes } from '@/Composables/useCourseEditorRoutes'
import type { AppPageProps } from "@/Types"
import { Button } from "@/Components/Button";
import { EmptyState } from "@/Components/EmptyState";
import { useForm, usePage } from "@inertiajs/vue3";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";
import { PlusIcon } from "lucide-vue-next";

const { courseRouteName } = useCourseEditorRoutes()

const props = defineProps<{
  id: string
  chapters: Array<any>
  isEditable: boolean
}>()

const page = usePage<AppPageProps & { revision: number }>()
const createChapterForm = useForm({})
const createChapter = () => {
  createChapterForm.transform(() => ({ revision: page.props.revision })).post(route(courseRouteName('course.chapters.store'), { course: props.id, _query: { lesson: 1 } }), {
    preserveScroll: true,
  })
}
</script>
