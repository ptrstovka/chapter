<template>
  <Link
    :href="route('studio.course.lessons.show', [courseId, chapterId, lessonId])"
    preserve-scroll
    :class="cn(
      'flex px-4 py-3 transition-colors cursor-pointer text-muted-foreground',
      { 'bg-accent text-foreground': active },
      { 'hover:bg-accent/50': !active },
    )"
  >
    <p class="text-sm">{{ title }}</p>
  </Link>
</template>

<script setup lang="ts">
import { cn } from "@/Utils";
import { Link } from '@inertiajs/vue3'

defineProps<{
  courseId: string
  chapterId: string
  lessonId: string
  title: string
  active: boolean
}>()
</script>
