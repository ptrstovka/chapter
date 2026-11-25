<template>
  <AuthenticatedLayout :header="false" fluid>
    <template #header>
      <CoursePreviewBanner
        v-if="preview"
        :preview="preview"
        :course-title="courseTitle"
        class="w-full overflow-hidden [scrollbar-gutter:stable] lg:pl-px"
      >
        <LinkButton :href="preview.courseUrl" variant="outline" class="hidden lg:inline-flex">
          {{ $t('Back to course preview') }}
        </LinkButton>
      </CoursePreviewBanner>
      <div v-else class="relative flex flex-row items-center lg:justify-center w-full">
        <div class="inline-flex items-center gap-4">
          <h2 class="text-sm sm:text-lg lg:text-xl lg:text-center font-semibold leading-tight">{{ courseTitle }}</h2>
        </div>

        <div class="absolute right-0 hidden overflow-hidden [scrollbar-gutter:stable] lg:inline-flex">
          <Badge v-if="courseCompleted" variant="positive">{{ $t('Course Completed!') }}</Badge>
          <CircularProgressBar v-else :value="progress" class="w-8 h-8 text-xs" />
        </div>

        <div class="absolute left-0 hidden lg:inline-flex">
          <LinkButton v-if="courseUrl" :href="courseUrl" variant="outline" class="gap-2">
            <XIcon class="w-4 h-4" /> {{ $t('Leave Course') }}
          </LinkButton>
        </div>
      </div>
    </template>

    <Head :title="lessonTitle" />
    <div>
      <div class="flex flex-row">
        <div class="hidden lg:block relative flex-shrink-0 w-96 border-x bg-background" style="height: calc(100vh - 4rem)">
          <CourseOutline ref="outline" :chapters="chapters" :preview="!!preview" class="absolute inset-0 flex flex-col overflow-y-auto" />
        </div>

        <div class="relative flex-1" style="height: calc(100vh - 4rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-scroll">
            <div class="lg:hidden border-b flex flex-row items-center justify-between py-3 px-4 sm:px-6">
              <div class="flex flex-wrap items-center gap-2">
                <Button @click="mobileOutline.activate" variant="outline"><ListTreeIcon class="text-muted-foreground" /> {{ $t('Lessons') }}</Button>

                <LinkButton v-if="courseUrl" :href="courseUrl" variant="outline">
                  <XIcon class="text-muted-foreground" />
                  <span>{{ $t(preview ? 'Back to course preview' : 'Leave Course') }}</span>
                </LinkButton>
              </div>

              <template v-if="!preview">
                <Badge v-if="courseCompleted" variant="positive">{{ $t('Course Completed!') }}</Badge>
                <CircularProgressBar v-else :value="progress" class="w-8 h-8 text-xs" />
              </template>
            </div>

            <div class="px-4 py-6 sm:px-6 lg:px-8">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </div>

    <Sheet :control="mobileOutline">
      <SheetContent side="left" class="overflow-y-auto">
        <CourseOutline :chapters="chapters" :preview="!!preview" />

        <SheetFooter>
          <Button @click="mobileOutline.deactivate" variant="outline">{{ $t('Close') }}</Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { CircularProgressBar } from '@/Components/CircularProgressBar'
import { Sheet, SheetContent, SheetFooter } from "@/Components/Sheet";
import { AuthenticatedLayout } from '@/Layouts'
import type { Chapter, CoursePreview } from "@/Pages/Courses";
import CourseOutline from "@/Pages/Courses/Components/CourseOutline.vue";
import CoursePreviewBanner from "@/Pages/Courses/Components/CoursePreviewBanner.vue";
import { Head } from '@inertiajs/vue3'
import { Button, LinkButton } from "@/Components/Button";
import { useToggle } from "@stacktrace/ui";
import { XIcon, ListTreeIcon } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Badge } from '@/Components/Badge'

const props = withDefaults(defineProps<{
  courseSlug: string | null
  courseTitle: string
  lessonTitle: string
  progress?: number
  remainingLessons: number
  chapters: Array<Chapter>
  preview?: CoursePreview
}>(), { progress: 0 })

const courseCompleted = computed(() => props.remainingLessons === 0)
const courseUrl = computed(() => props.preview?.courseUrl ?? (props.courseSlug ? route('courses.show', props.courseSlug) : null))

const lessons = computed(() => props.chapters.map(it => it.lessons).flatMap(it => it))
const currentLesson = computed(() => lessons.value.find(it => it.isCurrent))

const mobileOutline = useToggle()
const outline = ref()

onMounted(() =>{
  outline.value?.scrollToCurrentLesson()

  document.getElementsByTagName('html')[0].classList.remove('overflow-y-scroll')
})

onBeforeUnmount(() => {
  document.getElementsByTagName('html')[0].classList.add('overflow-y-scroll')
})

watch(currentLesson, () => {
  outline.value?.scrollToCurrentLesson()
})
</script>
