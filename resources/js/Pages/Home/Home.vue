<template>
  <Head title="Home" />

  <AuthenticatedLayout class="bg-background">
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-10">
        <EmptyState
          v-if="isEmpty"
          title="Nothing to Show Here"
          message="It looks like there are no courses in this section right now. Check back later or explore other categories to find something new!"
        />

        <CourseRow
          v-if="inProgress.length > 0"
          title="Pick Up Where You Left Off"
          description="Continue learning with your ongoing courses. Dive back in and keep making progress toward your goals."
          :courses="inProgress"
        />

        <CourseRow
          v-if="latest.length > 0"
          title="New Courses, Fresh Opportunities"
          description="Explore the latest courses added to our library. Stay updated with cutting-edge content and trending topics."
          :courses="latest"
          action="Browse Latest Courses"
          :url="route('courses', { _query: { sort: 'latest' } })"
        />

        <CourseRow
          v-if="popular.length > 0"
          title="Most-Loved by Learners"
          description="Discover the courses that everyone is talking about. Join the crowd and explore what’s making waves."
          :courses="popular"
          action="Browse Popular Courses"
          :url="route('courses', { _query: { sort: 'popular', hideCompleted: 'true' } })"
        />

        <CourseRow
          v-if="discover.length > 0"
          title="Feeling Adventurous?"
          description="Browse a surprise selection of courses. Who knows? You might stumble upon your next passion."
          :courses="discover"
          action="Browse All Courses"
          :url="route('courses')"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import type { Course } from '@/Components/Course'
import { Head } from '@inertiajs/vue3'
import { AuthenticatedLayout } from '@/Layouts'
import { computed } from 'vue'
import CourseRow from './Components/CourseRow.vue'
import { EmptyState } from '@/Components/EmptyState'

const props = defineProps<{
  inProgress: Array<Course>
  latest: Array<Course>
  popular: Array<Course>
  discover: Array<Course>
}>()

const isEmpty = computed(() =>
  props.inProgress.length === 0
  && props.latest.length === 0
  && props.popular.length === 0
  && props.discover.length === 0
)
</script>
