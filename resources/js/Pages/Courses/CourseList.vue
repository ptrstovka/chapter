<template>
  <Head :title="category || 'All Courses'" />

  <AuthenticatedLayout class="bg-background">
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-row gap-8">
          <div class="w-72">
            <h2 class="font-semibold text-xl leading-tight">Categories</h2>

            <ul class="mt-4 -ml-4 flex flex-col gap-1">
              <li v-for="category in categories">
                <Button
                  @click="filter.category = filter.category === category.value ? null : category.value"
                  :variant="filter.category == category.value ? 'default' : 'ghost'"
                  class="w-full justify-start line-clamp-1 text-left"
                  plain
                >{{ category.title }}</Button>
              </li>
              <li>
                <Button @click="filter.category = null" variant="ghost" class="w-full justify-start" plain>All Courses</Button>
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
              <h2 class="font-semibold text-xl leading-tight">{{ category || 'All Courses' }}</h2>

              <OptionSelect v-if="courses.total > 0" class="w-32" :options="sortOptions" v-model="filter.sort" />
            </div>

            <EmptyState
              v-if="courses.total === 0"
              title="No Courses Available"
              message="There are no video courses available right now. Please check back later!"
              class="mt-12"
            />

            <div v-if="courses.data.length > 0" class="grid grid-cols-3 my-6 gap-4 w-full">
              <CourseCard v-for="course in courses.data" :course="course" />
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
import { EmptyState } from '@/Components/EmptyState'
import { useFilter } from '@/Composables'
import { Head } from '@inertiajs/vue3'
import { AuthenticatedLayout } from '@/Layouts'
import { Button } from '@/Components/Button'
import type { Option, Paginator } from '@/Types'
import { computed } from 'vue'
import { OptionSelect } from '@/Components/Select'
import { SimplePagination } from '@/Components/Pagination'
import { CheckboxControl } from '@/Components/Checkbox'
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'

defineProps<{
  category: string | null
  categories: Array<{
    value: string
    title: string
  }>
  courses: Paginator<Course>
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
