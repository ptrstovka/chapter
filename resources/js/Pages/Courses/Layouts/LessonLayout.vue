<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout :header="false" fluid>
    <template #header>
      <div class="relative flex flex-row items-center justify-center w-full">
        <div class="inline-flex items-center gap-4">
          <h2 class="text-xl font-semibold leading-tight">{{ courseTitle }}</h2>
        </div>

        <div class="absolute right-0 inline-flex">
          <Badge v-if="courseCompleted" variant="positive">{{ $t('Course Completed!') }}</Badge>
          <CircularProgressBar v-else :value="progress" class="w-8 h-8 text-xs" />
        </div>

        <div class="absolute left-0 inline-flex">
          <LinkButton :href="route('courses.show', courseSlug)" variant="outline" class="gap-2">
            <XIcon class="w-4 h-4" />
            {{ $t('Leave Course') }}
          </LinkButton>
        </div>
      </div>
    </template>

    <div>
      <div class="flex flex-row">
        <div class="relative flex-shrink-0 w-96 border-x bg-background" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto " ref="itemsWrapper">
            <ul class="divide-y">
              <li v-for="item in flattenItems" ref="items">
                <template v-if="item.type === 'chapter'">
                  <div class="py-3 pl-4 sm:pl-6 lg:pl-8">
                    <p class="text-xs font-medium">{{ item.chapter.title }}</p>
                  </div>
                </template>
                <template v-else-if="item.type === 'lesson'">
                  <Link preserve-state :href="item.lesson.url" class="flex flex-row px-4 py-3 transition-colors cursor-pointer sm:px-6 lg:px-8" :class="{ 'bg-accent': item.lesson.isCurrent, 'hover:bg-accent': !item.lesson.isCurrent }">
                    <div
                      :class="cn('w-8 h-8 flex-shrink-0 bg-secondary text-secondary-foreground rounded-full inline-flex items-center justify-center', {
                          'bg-primary text-primary-foreground': item.lesson.isCurrent,
                          'bg-green-50 text-green-700 dark:bg-green-800 dark:text-green-50': !item.lesson.isCurrent && item.lesson.isCompleted,
                        })"
                    >
                      <PlayIcon v-if="item.lesson.isCurrent" class="w-4 h-4" />
                      <template v-else>
                        <CheckIcon v-if="item.lesson.isCompleted" class="w-4 h-4" />
                        <HourglassIcon v-else class="w-4 h-4" />
                      </template>
                    </div>
                    <div class="flex flex-col flex-1 px-2">
                      <p class="text-xs font-medium" :title="item.lesson.title">{{ item.lesson.title }}</p>
                      <div class="inline-flex flex-row gap-2 mt-0.5">
                        <p class="text-xs text-muted-foreground">{{ $t('Lesson :value', { value: `${item.lesson.no}` }) }}</p>

                        <div v-if="item.lesson.duration" class="inline-flex flex-row items-center gap-1 text-muted-foreground">
                          <TimerIcon class="w-3 h-3" />
                          <p class="text-xs">{{ item.lesson.duration }}</p>
                        </div>
                      </div>
                    </div>
                  </Link>
                </template>
              </li>
            </ul>
          </div>
        </div>

        <div class="relative flex-1" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col px-4 py-6 overflow-y-scroll sm:px-6 lg:px-8">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { CircularProgressBar } from '@/Components/CircularProgressBar'
import { AuthenticatedLayout } from '@/Layouts'
import { cn } from '@/Utils'
import { Head, Link } from '@inertiajs/vue3'
import { LinkButton } from '@/Components/Button'
import { PlayIcon, TimerIcon, HourglassIcon, CheckIcon, XIcon } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from "vue";
import { Badge } from '@/Components/Badge'

interface Lesson {
  slugId: string
  title: string
  no: number
  isCurrent: boolean
  duration: string | null
  url: string
  isCompleted: boolean
}

interface Chapter {
  no: number
  title: string
  lessons: Array<Lesson>
}

interface ChapterItem {
  type: 'chapter'
  chapter: Chapter
}

interface LessonItem {
  type: 'lesson'
  lesson: Lesson
}

type ListItem = ChapterItem | LessonItem

const props = defineProps<{
  courseSlug: string
  courseTitle: string
  lessonTitle: string
  progress: number
  remainingLessons: number
  chapters: Array<Chapter>
}>()

const courseCompleted = computed(() => props.remainingLessons === 0)

const flattenItems = computed<Array<ListItem>>(() => props.chapters.reduce((acc: Array<ListItem>, val) => {
  acc.push({
    type: 'chapter',
    chapter: {...val, lessons: []},
  })
  const lessons: Array<ListItem> = val.lessons.map(it => ({ type: 'lesson', lesson: it }))
  acc.push(...lessons)

  return acc
}, []))

const itemRefs = useTemplateRef<Array<HTMLLIElement>>('items')
const itemsWrapper = ref<HTMLDivElement>()

const scrollToCurrentLesson = () => {
  const currentIdx = flattenItems.value.findIndex(it => it.type === 'lesson' && it.lesson.isCurrent)

  const els = itemRefs.value

  if (!els || currentIdx < 0 || currentIdx >= els.length) {
    return
  }

  const wrapper = itemsWrapper.value
  if (! wrapper) {
    return
  }

  const el = els[currentIdx]

  const elRect = el.getBoundingClientRect();
  const wrapperRect = wrapper.getBoundingClientRect();

  const isVisible = elRect.top >= wrapperRect.top && elRect.bottom <= wrapperRect.bottom

  if (! isVisible) {
    el.scrollIntoView({
      behavior: 'smooth',
    })
  }
}

const lessons = computed(() => props.chapters.map(it => it.lessons).flatMap(it => it))
const currentLesson = computed(() => lessons.value.find(it => it.isCurrent))

onMounted(() =>{
  scrollToCurrentLesson()

  document.getElementsByTagName('html')[0].classList.remove('overflow-y-scroll')
})

onBeforeUnmount(() => {
  document.getElementsByTagName('html')[0].classList.add('overflow-y-scroll')
})

watch(currentLesson, () => {
  scrollToCurrentLesson()
})
</script>
