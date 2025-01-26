<template>
  <Head :title="category || 'All courses'" />

  <AuthenticatedLayout class="bg-background">
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-row gap-8">
          <div class="w-72">
            <h2 class="font-semibold text-xl leading-tight">Categories</h2>

            <ul class="mt-4 -ml-4 flex flex-col gap-1">
              <li v-for="category in categories">
                <Button
                  @click="filter.category = category.value"
                  :variant="filter.category == category.value ? 'default' : 'ghost'"
                  class="w-full justify-start"
                  plain
                >{{ category.title }}</Button>
              </li>
              <li>
                <Button @click="filter.category = null" variant="ghost" class="w-full justify-start" plain>All courses</Button>
              </li>
            </ul>

            <h2 class="font-semibold text-xl leading-tight mt-8 block">Filter</h2>

            <ul class="mt-4">
              <li>
                <CheckboxControl v-model="filter.hideCompleted">Hide Completed</CheckboxControl>
              </li>
            </ul>
          </div>

          <div class="w-full">
            <div class="flex flex-row justify-between">
              <h2 class="font-semibold text-xl leading-tight">{{ category || 'All courses' }}</h2>

              <OptionSelect v-if="courses.total > 0" class="w-32" :options="sortOptions" v-model="filter.sort" />
            </div>

            <div v-if="courses.total === 0" class="overflow-hidden flex items-center flex-col mt-12">
              <div class="flex flex-col max-w-sm items-center relative">
                <EmptyPattern class="text-foreground/10 h-[520px] -translate-y-2/3 absolute" />

                <div class="w-16 h-16 rounded-full bg-muted text-foreground inline-flex items-center justify-center mb-8">
                  <TvIcon class="w-6 h-6" />
                </div>

                <p class="font-semibold text-center">No Courses Available</p>
                <p class="max-w-md text-center text-muted-foreground text-sm mt-1">There are no video courses available right now. Please check back later!</p>
              </div>
            </div>

            <div v-if="courses.data.length > 0" class="grid grid-cols-3 my-6 gap-4 w-full">
              <Link v-for="course in courses.data" :href="course.url" class="border w-full bg-background rounded-md overflow-hidden">
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
            </div>

            <div v-if="courses.total > 0" class="flex flex-row justify-end w-full">
              <SimplePagination :paginator="courses" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { useFilter } from '@/Composables'
import { Head, Link } from '@inertiajs/vue3'
import { AuthenticatedLayout } from '@/Layouts'
import { Button } from '@/Components/Button'
import type { Option, Paginator } from '@/Types'
import { ContactIcon, TimerIcon, ImageOffIcon, TvIcon, CheckIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import { OptionSelect } from '@/Components/Select'
import { SimplePagination } from '@/Components/Pagination'
import { Badge } from '@/Components/Badge'
import { CheckboxControl } from '@/Components/Checkbox'
import EmptyPattern from './Components/EmptyPattern.vue'

defineProps<{
  category: string | null
  categories: Array<{
    value: string
    title: string
  }>
  courses: Paginator<{
    title: string
    url: string
    coverImageUrl: string | null
    duration: string | null
    author: {
      name: string
    }
    enrollment: {
      isCompleted: boolean
      progress: number
    } | null
  }>
}>()

const sortOptions = computed<Array<Option>>(() => [
  { value: 'latest', label: 'Latest' },
  { value: 'popular', label: 'Popular' },
  { value: 'title-asc', label: 'A-Z' },
  { value: 'title-desc', label: 'Z-A' },
])

const filter = useFilter(() => ({
  category: null as string | null,
  sort: 'latest',
  hideCompleted: false,
}))
</script>
