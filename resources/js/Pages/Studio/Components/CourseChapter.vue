<template>
  <div>
    <Link
      :href="route('studio.course.chapters.show', [courseId, chapterId])"
      :class="cn(
        'relative flex h-10 flex items-center px-4 border-b cursor-pointer transition-colors',
        { 'bg-accent': active },
        { 'hover:bg-accent/50': !active },
      )"
      preserve-scroll
    >
      <PlaceholderPattern class="opacity-80" />

      <p class="text-sm font-medium">{{ title }}</p>
    </Link>

    <slot />

    <div class="px-4 py-2">
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
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { PlaceholderPattern } from "@/Components/Skeleton";
import { cn } from "@/Utils";
import { Link, useForm } from "@inertiajs/vue3";
import { PlusIcon } from "lucide-vue-next";

const props = defineProps<{
  courseId: string
  chapterId: string
  title: string
  active: boolean
}>()

const createLessonForm = useForm({})
const createLesson = () => {
  createLessonForm.post(route('studio.course.lessons.store', [props.courseId, props.chapterId]), {
    preserveScroll: true,
  })
}
</script>
