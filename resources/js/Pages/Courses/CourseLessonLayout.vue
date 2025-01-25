<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout :header="false" fluid>
    <template #header>
      <div class="flex flex-row items-center justify-between w-full">
        <div class="inline-flex items-center gap-4">
          <h2 class="font-semibold text-xl leading-tight">{{ courseTitle }}</h2>
          <Badge v-if="courseCompleted" variant="positive">Course completed!</Badge>
          <Badge v-else>{{ progress }}% completed</Badge>
        </div>

        <LinkButton :href="route('courses.show', courseSlug)" variant="outline" class="gap-2">
          Leave Course
          <XIcon class="w-4 h-4" />
        </LinkButton>
      </div>
    </template>

    <div>
      <div class="flex flex-row">
        <div class="w-96 flex-shrink-0 border-x relative" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto bg-background" ref="itemsWrapper">
            <ul class="divide-y">
              <li v-for="item in flattenItems" ref="items">
                <template v-if="item.type === 'chapter'">
                  <div class="py-3 pl-4 sm:pl-6 lg:pl-8">
                    <p class="text-xs font-medium">{{ item.chapter.title }}</p>
                  </div>
                </template>
                <template v-else-if="item.type === 'lesson'">
                  <Link preserve-state :href="item.lesson.url" class="flex flex-row cursor-pointer py-3 px-4 sm:px-6 lg:px-8 transition-colors" :class="{ 'bg-accent': item.lesson.isCurrent, 'hover:bg-accent': !item.lesson.isCurrent }">
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
                    <div class="flex flex-1 flex-col px-2">
                      <p class="text-xs font-medium" :title="item.lesson.title">{{ item.lesson.title }}</p>
                      <div class="inline-flex flex-row gap-2 mt-0.5">
                        <p class="text-xs text-muted-foreground">Lesson {{ item.lesson.no }}</p>

                        <div v-if="item.lesson.duration" class="inline-flex flex-row gap-1 items-center text-muted-foreground">
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
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
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
})

watch(currentLesson, () => {
  scrollToCurrentLesson()
})
</script>
