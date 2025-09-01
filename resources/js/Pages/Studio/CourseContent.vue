<template>
  <CourseContentLayout>
    <div v-if="chapters.length === 0" class="items-center flex flex-col">
      <EmptyState
        class="pt-20 w-full"
        :title="$t('This course does not have any lessons.')"
        :message="$t('Get started by adding first lesson.')"
      >
        <Button
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
import { Button } from "@/Components/Button";
import { EmptyState } from "@/Components/EmptyState";
import { useForm } from "@inertiajs/vue3";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";
import { PlusIcon } from "lucide-vue-next";

const props = defineProps<{
  id: string
  chapters: Array<any>
}>()

const createChapterForm = useForm({})
const createChapter = () => {
  createChapterForm.post(route('studio.course.chapters.store', { course: props.id, _query: { lesson: 1 } }), {
    preserveScroll: true,
  })
}
</script>
