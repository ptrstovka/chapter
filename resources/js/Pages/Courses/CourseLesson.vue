<template>
  <Player
    v-if="video"
    :src="video.url"
    :poster="video.posterImageUrl"
    class="mb-6"
  />

  <div class="flex flex-row justify-between items-start gap-6">
    <h2 class="font-semibold text-xl leading-tight">{{ lessonTitle }}</h2>

    <div class="inline-flex flex-row gap-2">
      <template v-if="! courseCompleted">
        <Button v-if="isCompleted" variant="positive" :processing="markCompletedForm.processing" @click="markNotCompleted">
          <CheckIcon class="w-4 h-4" /> Completed
        </Button>
        <Button v-else-if="!isCompleted && remainingLessons > 1" :processing="markCompletedForm.processing" @click="markCompleted" variant="outline">
          <CheckIcon class="w-4 h-4" /> Mark Completed
        </Button>
      </template>

      <LinkButton v-if="prevLesson" :href="prevLesson.url" class="gap-2" variant="outline">
        <RewindIcon class="w-4 h-4" />
        Previous Lesson
      </LinkButton>
      <Button v-if="remainingLessons == 1 && !isCompleted" @click="finish">
        <CheckIcon class="w-4 h-4" />
        Finish Course
      </Button>
      <Button v-else-if="nextLesson" @click="navigateToNextLesson">
        Next Lesson
        <FastForwardIcon class="w-4 h-4" />
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

  <Dialog :control="completeDialog">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Congratulations! 🎉</DialogTitle>
      </DialogHeader>
      <p>You did it! You’ve officially completed the course.</p>
      <p>Taking the time and effort to learn something new is no small feat, and you should be proud of yourself for making it through to the end.</p>
      <DialogFooter>
        <Button @click="exit">Continue</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Player } from '@/Components/Player'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/Tabs'
import type { VideoSource } from '@/Types'
import { router, useForm } from '@inertiajs/vue3'
import { Button, LinkButton } from '@/Components/Button'
import { CheckIcon, RewindIcon, FastForwardIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import sortBy from "lodash/sortBy"
import { RootLayout } from '@/Layouts'
import LessonLayout from './CourseLessonLayout.vue'
import confetti from "canvas-confetti"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/Dialog'
import { useToggle } from '@/Composables'

interface Lesson {
  slugId: string
  title: string
  no: number
  isCurrent: boolean
  duration: string | null
  url: string
  isCompleted: boolean
}

defineOptions({
  layout: [RootLayout, LessonLayout],
})

const props = defineProps<{
  id: string
  isCompleted: boolean
  courseSlug: string
  courseTitle: string
  lessonTitle: string
  progress: number
  video: VideoSource | null
  description: string | null
  remainingLessons: number
  resources: Array<{}>
  chapters: Array<{
    no: number
    title: string
    lessons: Array<Lesson>
  }>
}>()

const lessons = computed<Array<Lesson>>(() => sortBy(props.chapters.map(it => it.lessons).flatMap(it => it), it => it.no))

const courseCompleted = computed(() => props.remainingLessons === 0)

const prevLesson = computed<Lesson | null>(() => {
  const idx = lessons.value.findIndex(it => it.isCurrent)
  const nextIds = idx - 1

  if (nextIds >= 0) {
    return lessons.value[nextIds]
  }

  return null
})

const nextLesson = computed<Lesson | null>(() => {
  const idx = lessons.value.findIndex(it => it.isCurrent)
  const nextIds = idx + 1

  if (nextIds <= lessons.value.length - 1) {
    return lessons.value[nextIds]
  }

  return null
})

const markCompletedForm = useForm({})
const markCompleted = () => {
  if (markCompletedForm.processing) {
    return
  }

  markCompletedForm.post(route('completed-lessons.store', props.id))
}
const markNotCompleted = () => {
  if (markCompletedForm.processing) {
    return
  }

  markCompletedForm.delete(route('completed-lessons.store', props.id))
}

const navigateToNextLesson = () => {
  router.post(route('lessons.next', props.courseSlug), {
    from: props.id,
  })
}

const completeDialog = useToggle()
const finish = () => {
  router.post(route('completed-lessons.store', props.id), {}, {
    preserveScroll: true,
    showProgress: false,
  })

  const end = Date.now() + 2 * 1000
  const colors = ['#a786ff', '#fd8bbc', '#eca184', '#f8deb1']

  function frame() {
    if (Date.now() > end) {
      return
    }

    confetti({
      particleCount: 2,
      angle: 60,
      spread: 55,
      startVelocity: 60,
      origin: { x: 0, y: 0.8 },
      colors: colors,
    })

    confetti({
      particleCount: 2,
      angle: 120,
      spread: 55,
      startVelocity: 60,
      origin: { x: 1, y: 0.8 },
      colors: colors,
    })

    requestAnimationFrame(frame)
  }

  frame()
  completeDialog.activate()
}

const exit = () => {
  completeDialog.deactivate()
  router.visit(route('courses.show', props.courseSlug))
}
</script>
