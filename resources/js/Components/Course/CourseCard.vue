<template>
  <Card class="p-0 overflow-hidden">
    <CardContent class="p-0">
      <Link :href="course.url">
        <div class="relative aspect-video">
          <img :class="{ 'grayscale': course.enrollment && course.enrollment.isCompleted }" class="object-cover w-full h-full" v-if="course.coverImageUrl" :src="course.coverImageUrl" :alt="`Cover image of ${course.title} course`">
          <div v-else class="flex items-center justify-center w-full h-full bg-stone-50 dark:bg-background">
            <ImageOffIcon class="w-5 h-5 text-stone-400" />
          </div>

          <Badge v-if="course.enrollment && course.enrollment.isCompleted" class="absolute top-2 left-2 gap-0.5 pl-1.5" variant="primary">
            <CheckIcon class="w-4 h-4 mt-px" />
            {{ $t('Completed') }}
          </Badge>
          <Badge v-if="course.enrollment && !course.enrollment.isCompleted" class="absolute top-2 left-2">{{ $t(':value% In Progress', { value: `${course.enrollment.progress}` }) }}</Badge>

          <CourseFavoriteButton class="absolute bottom-0 left-0" :slug="course.slug" :is-favorite="course.isFavorite" />
        </div>
        <div class="px-2 py-3 border-t">
          <div class="flex flex-row gap-2 mb-2">
            <p class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
              <ContactIcon class="w-4 h-4" />
              {{ course.author.name }}
            </p>
            <p v-if="course.duration" class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground tabular-nums">
              <TimerIcon class="w-4 h-4" />
              {{ course.duration }}
            </p>
          </div>

          <p class="font-semibold">{{ course.title }}</p>
        </div>
      </Link>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Badge } from '@/Components/Badge'
import { Card, CardContent } from '@/Components/Card'
import { Link } from '@inertiajs/vue3'
import { CheckIcon, ContactIcon, ImageOffIcon, TimerIcon } from 'lucide-vue-next'
import type { Course } from '.'
import CourseFavoriteButton from './CourseFavoriteButton.vue'

defineProps<{
  course: Course
}>()
</script>
