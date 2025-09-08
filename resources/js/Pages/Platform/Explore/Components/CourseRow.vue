<template>
  <div class="flex flex-col">
    <div class="flex flex-row justify-between">
      <h2 class="font-semibold md:text-xl leading-tight">{{ title }}</h2>
    </div>
    <p v-if="description" class="text-muted-foreground text-sm md:max-w-lg mt-1">{{ description }}</p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 mt-4 gap-4">
      <CourseCard v-for="course in courses" :course="course" />
    </div>

    <div class="flex flex-row justify-end mt-4" v-if="url && action">
      <Link class="font-bold text-sm gap-2 inline-flex items-center" :href="url">
        {{ action }}
        <MoveRightIcon class="w-4 h-4" />
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'
import { Link } from '@inertiajs/vue3'
import { MoveRightIcon } from 'lucide-vue-next'

defineProps<{
  title: string
  description?: string | null
  courses: Array<Course>
  url?: string | null
  action?: string | null
}>()
</script>
