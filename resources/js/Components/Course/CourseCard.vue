<template>
  <Card class="p-0 overflow-hidden">
    <CardContent class="p-0">
      <Link :href="course.url">
        <div class="h-44 relative">
          <img :class="{ 'grayscale': course.enrollment && course.enrollment.isCompleted }" class="w-full h-full object-cover" v-if="course.coverImageUrl" :src="course.coverImageUrl" :alt="`Cover image of ${course.title} course`">
          <div v-else class="w-full h-full bg-stone-50 flex items-center justify-center">
            <ImageOffIcon class="w-5 h-5 text-stone-400" />
          </div>

          <Badge v-if="course.enrollment && course.enrollment.isCompleted" class="absolute top-2 left-2 gap-0.5 pl-1.5" variant="positive">
            <CheckIcon class="w-4 h-4 mt-px" />
            Completed
          </Badge>
          <Badge v-if="course.enrollment && !course.enrollment.isCompleted" class="absolute top-2 left-2">{{ course.enrollment.progress }}% In Progress</Badge>
        </div>
        <div class="border-t px-2 py-3">
          <p class="font-semibold">{{ course.title }}</p>

          <div class="flex flex-row mt-2 gap-2">
            <p class="text-xs font-medium text-muted-foreground inline-flex items-center gap-1">
              <ContactIcon class="w-4 h-4" />
              {{ course.author.name }}
            </p>
            <p v-if="course.duration" class="text-xs font-medium text-muted-foreground inline-flex tabular-nums items-center gap-1">
              <TimerIcon class="w-4 h-4" />
              {{ course.duration }}
            </p>
          </div>
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

defineProps<{
  course: Course
}>()
</script>
