<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout :header="false" fluid>
    <template #header>
      <div class="flex flex-row items-center justify-between w-full">
        <h2 class="font-semibold text-xl leading-tight">{{ courseTitle }}</h2>

        <LinkButton :href="route('courses.show', courseSlug)" variant="outline">Exit Course</LinkButton>
      </div>
    </template>

    <div>
      <div class="flex flex-row">
        <div class="w-96 flex-shrink-0 border-x relative" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto bg-background">
            <ul>
              <li v-for="(chapter, idx) in chapters">
                <div class="bg-secondary py-3 border-b pl-4 sm:pl-6 lg:pl-8" :class="{ 'border-t': idx > 0 }">
                  <p class="text-xs font-medium">Chapter {{ chapter.no }}: {{ chapter.title }}</p>
                </div>
                <ul class="divide-y">
                  <li v-for="lesson in chapter.lessons">
                    <Link preserve-state :href="lesson.url" class="flex flex-row cursor-pointer py-3 px-4 sm:px-6 lg:px-8 hover:bg-accent transition-colors">
                      <div
                        :class="cn('w-8 h-8 flex-shrink-0 bg-secondary text-secondary-foreground rounded-full inline-flex items-center justify-center', {
                          'bg-primary text-primary-foreground border-none': lesson.isCurrent,
                        })"
                      >
                        <PlayIcon v-if="lesson.isCurrent" class="w-4 h-4" />
                        <HourglassIcon v-else class="w-4 h-4" />
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
            <Player
              v-if="video"
              :src="video.url"
              :poster="video.posterImageUrl"
              class="mb-6"
            />

            <div class="flex flex-row justify-between items-start gap-6">
              <h2 class="font-semibold text-xl leading-tight">{{ lessonTitle }}</h2>

              <div class="inline-flex flex-row gap-2">
                <Button v-if="nextLesson" variant="outline">Mark Completed</Button>

                <LinkButton preserve-state v-if="nextLesson" :href="nextLesson.url" class="gap-2">
                  Next Lesson
                  <SkipForwardIcon class="w-4 h-4" />
                </LinkButton>
                <Button v-else class="gap-2">
                  Finish Course
                </Button>
              </div>
            </div>

            <Tabs v-if="description || resources.length > 0" :default-value="description ? 'description' : 'resources'">
              <TabsList class="mt-6">
                <TabsTrigger v-if="description" value="description">Description</TabsTrigger>
                <TabsTrigger v-if="resources.length > 0" value="resources">Resources</TabsTrigger>
              </TabsList>
              <TabsContent value="description" v-if="description">
                <div class="editor-content" v-html="description"></div>
              </TabsContent>
              <TabsContent value="resources">
                <p>resources</p>
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Player } from '@/Components/Player'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/Tabs'
import { AuthenticatedLayout } from '@/Layouts'
import type { VideoSource } from '@/Types'
import { cn } from '@/Utils'
import { Head, Link } from '@inertiajs/vue3'
import { Button, LinkButton } from '@/Components/Button'
import { PlayIcon, TimerIcon, HourglassIcon, SkipForwardIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import sortBy from "lodash/sortBy"

interface Lesson {
  title: string
  no: number
  isCurrent: boolean
  duration: string | null
  url: string
}

const props = defineProps<{
  courseSlug: string
  courseTitle: string
  lessonTitle: string
  video: VideoSource | null
  description: string | null
  resources: Array<{}>
  chapters: Array<{
    no: number
    title: string
    lessons: Array<Lesson>
  }>
}>()

const lessons = computed<Array<Lesson>>(() => sortBy(props.chapters.map(it => it.lessons).flatMap(it => it), it => it.no))

const nextLesson = computed<Lesson | null>(() => {
  const idx = lessons.value.findIndex(it => it.isCurrent)
  const nextIds = idx + 1

  if (nextIds <= lessons.value.length - 1) {
    return lessons.value[nextIds]
  }

  return null
})
</script>
