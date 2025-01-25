<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout :header="false" fluid>
    <template #header>
      <div class="flex flex-row items-center justify-between w-full">
        <h2 class="font-semibold text-xl leading-tight">{{ courseTitle }} - {{ progress }}%</h2>

        <LinkButton :href="route('courses.show', courseSlug)" variant="outline" class="gap-2">
          Leave Course
          <XIcon class="w-4 h-4" />
        </LinkButton>
      </div>
    </template>

    <div>
      <div class="flex flex-row">
        <div class="w-96 flex-shrink-0 border-x relative" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto bg-background">
            <ul>
              <li v-for="(chapter, idx) in chapters">
                <div class="py-3 border-b pl-4 sm:pl-6 lg:pl-8" :class="{ 'border-t': idx > 0 }">
                  <p class="text-xs font-medium">Chapter {{ chapter.no }}: {{ chapter.title }}</p>
                </div>
                <ul class="divide-y">
                  <li v-for="lesson in chapter.lessons">
                    <Link preserve-state :href="lesson.url" class="flex flex-row cursor-pointer py-3 px-4 sm:px-6 lg:px-8 transition-colors" :class="{ 'bg-accent': lesson.isCurrent, 'hover:bg-accent': !lesson.isCurrent }">
                      <div
                        :class="cn('w-8 h-8 flex-shrink-0 bg-secondary text-secondary-foreground rounded-full inline-flex items-center justify-center', {
                          'bg-primary text-primary-foreground border-none': lesson.isCurrent,
                          'bg-green-50 border-green-200 text-green-700 dark:bg-green-800 dark:text-green-50 dark:border-green-800 border-none': !lesson.isCurrent && lesson.isCompleted,
                        })"
                      >
                        <PlayIcon v-if="lesson.isCurrent" class="w-4 h-4" />
                        <template v-else>
                          <CheckIcon v-if="lesson.isCompleted" class="w-4 h-4" />
                          <HourglassIcon v-else class="w-4 h-4" />
                        </template>
                      </div>
                      <div class="flex flex-1 flex-col px-2">
                        <p class="text-xs font-medium" :title="lesson.title">{{ lesson.title }}</p>
                        <div class="inline-flex flex-row gap-2 mt-0.5">
                          <p class="text-xs text-muted-foreground">Lesson {{ lesson.no }}</p>

                          <div v-if="lesson.duration" class="inline-flex flex-row gap-1 items-center text-muted-foreground">
                            <TimerIcon class="w-3 h-3" />
                            <p class="text-xs">{{ lesson.duration }}</p>
                          </div>
                        </div>
                      </div>
                    </Link>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>

        <div class="flex-1 relative" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { AuthenticatedLayout } from '@/Layouts'
import { cn } from '@/Utils'
import { Head, Link } from '@inertiajs/vue3'
import { LinkButton } from '@/Components/Button'
import { PlayIcon, TimerIcon, HourglassIcon, CheckIcon, XIcon } from 'lucide-vue-next'

defineProps<{
  courseSlug: string
  courseTitle: string
  lessonTitle: string
  progress: number
  chapters: Array<{
    no: number
    title: string
    lessons: Array<{
      slugId: string
      title: string
      no: number
      isCurrent: boolean
      duration: string | null
      url: string
      isCompleted: boolean
    }>
  }>
}>()
</script>
