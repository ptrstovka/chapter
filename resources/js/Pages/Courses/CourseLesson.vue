<template>
  <div v-if="video" class="mb-6 relative">
    <Player
      :src="video.url"
      :poster="video.posterImageUrl"
      @ended="onPlaybackEnded"
      :autoplay="shouldAutoPlay === 'on'"
    >
      <template #settings>
        <PlayerSubmenu :label="$t('Autoplay')" :hint="shouldAutoPlay === 'on' ? $t('On') : $t('Off')">
          <template #icon>
            <PlayCircleIcon class="size-5" />
          </template>
          <template #content>
            <PlayerMenuRadioGroup v-model="shouldAutoPlay">
              <PlayerMenuRadioItem value="on">{{ $t('On') }}</PlayerMenuRadioItem>
              <PlayerMenuRadioItem value="off">{{ $t('Off') }}</PlayerMenuRadioItem>
            </PlayerMenuRadioGroup>
          </template>
        </PlayerSubmenu>

        <PlayerSubmenu :label="$t('Auto next lesson')" :hint="shouldPlayNextLesson === 'on' ? $t('On') : $t('Off')">
          <template #icon>
            <SkipForwardIcon class="size-5" />
          </template>
          <template #content>
            <PlayerMenuRadioGroup v-model="shouldPlayNextLesson">
              <PlayerMenuRadioItem value="on">{{ $t('On') }}</PlayerMenuRadioItem>
              <PlayerMenuRadioItem value="off">{{ $t('Off') }}</PlayerMenuRadioItem>
            </PlayerMenuRadioGroup>
          </template>
        </PlayerSubmenu>
      </template>
    </Player>

    <LessonCompleted
      v-if="showLessonCompleted"
      class="absolute inset-0 z-50"
      v-motion
      :initial="{ opacity: 0 }"
      :enter="{ opacity: 1, transition: { duration: 300, easing: 'easeInOut', delay: 0 } }"
      :auto="shouldPlayNextLesson == 'on'"
      :next="remainingLessons == 1 && !isCompleted ? false : !!nextLesson"
      @next="navigateToNextLesson"
    />
  </div>

  <div class="flex flex-row justify-between items-start gap-6">
    <h2 class="font-semibold text-xl leading-tight">{{ lessonTitle }}</h2>

    <div class="inline-flex flex-row gap-3">
      <template v-if="! courseCompleted">
        <Button v-if="isCompleted" variant="positive" :processing="markCompletedForm.processing" @click="markNotCompleted">
          <CheckIcon class="w-4 h-4" /> {{ $t('Lesson Completed') }}
        </Button>
        <Button v-else-if="!isCompleted && remainingLessons > 1" :processing="markCompletedForm.processing" @click="markCompleted" variant="outline">
          <CheckIcon class="w-4 h-4" /> {{ $t('Mark Completed') }}
        </Button>
      </template>

      <LinkButton v-if="prevLesson" :href="prevLesson.url" class="gap-2" variant="outline">
        <RewindIcon class="w-4 h-4" />
        {{ $t('Previous Lesson') }}
      </LinkButton>
      <Button v-if="remainingLessons == 1 && !isCompleted" @click="finish">
        <CheckIcon class="w-4 h-4" />
        {{ $t('Finish Course') }}
      </Button>
      <Button v-else-if="nextLesson" @click="navigateToNextLesson">
        {{ $t('Next Lesson') }}
        <FastForwardIcon class="w-4 h-4" />
      </Button>
    </div>
  </div>

  <Tabs v-if="description || resources.length > 0" :default-value="description ? 'description' : 'resources'">
    <TabsList class="mt-6">
      <TabsTrigger v-if="description" value="description">{{ $t('Description') }}</TabsTrigger>
      <TabsTrigger v-if="resources.length > 0" value="resources">{{ $t('Resources') }}</TabsTrigger>
    </TabsList>
    <TabsContent value="description" v-if="description">
      <div class="editor-content mt-4" v-html="description"></div>
    </TabsContent>
    <TabsContent value="resources">
      <ul class="max-w-xs flex flex-col gap-2 mt-4">
        <ResourceItem v-for="resource in resources" :resource="resource" />
      </ul>
    </TabsContent>
  </Tabs>

  <Dialog :control="completeDialog">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ $t('Congratulations!') }} 🎉</DialogTitle>
      </DialogHeader>
      <p>{{ $t("You did it! You’ve officially completed the course.") }}</p>
      <p>{{ $t('Taking the time and effort to learn something new is no small feat, and you should be proud of yourself for making it through to the end.') }}</p>
      <DialogFooter>
        <Button @click="exit">{{ $t('Continue') }}</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Player, PlayerSubmenu, PlayerMenuRadioItem, PlayerMenuRadioGroup } from '@/Components/Player'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/Tabs'
import type { VideoSource } from '@/Types'
import { router, useForm } from '@inertiajs/vue3'
import { Button, LinkButton } from '@/Components/Button'
import { useLocalStorage } from '@vueuse/core'
import { CheckIcon, RewindIcon, FastForwardIcon, PlayCircleIcon, SkipForwardIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import sortBy from "lodash/sortBy"
import { RootLayout } from '@/Layouts'
import LessonLayout from './Layouts/LessonLayout.vue'
import confetti from "canvas-confetti"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/Components/Dialog'
import { useToggle } from '@stacktrace/ui'
import LessonCompleted from './Components/LessonCompleted.vue'
import ResourceItem from './Components/ResourceItem.vue'
import { type Resource } from '.'

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
  resources: Array<Resource>
  chapters: Array<{
    no: number
    title: string
    lessons: Array<Lesson>
  }>
}>()

const showLessonCompleted = ref(false)
watch(() => props.id, () => {
  showLessonCompleted.value = false
})

const shouldAutoPlay = useLocalStorage('PlayerAutoPlay', 'on')
const shouldPlayNextLesson = useLocalStorage('PlayNextLesson', 'on')

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
  completeDialog.activate()

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
}

const exit = () => {
  completeDialog.deactivate()
  router.visit(route('courses.show', props.courseSlug))
}

const onPlaybackEnded = () => {
  showLessonCompleted.value = true

  if (props.remainingLessons === 1 && !props.isCompleted && !completeDialog.active.value) {
    finish()
  }
}
</script>
