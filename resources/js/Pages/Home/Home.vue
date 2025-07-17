<template>
  <Head :title="$t('Home')" />

  <AuthenticatedLayout class="bg-background">
    <div class="py-8">
      <div class="flex flex-col gap-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <EmptyState
          v-if="isEmpty"
          :title="$t('Nothing to Show Here')"
          :message="$t('It looks like there are no courses in this section right now. Check back later or explore other categories to find something new!')"
        />

        <CourseRow
          v-if="inProgress.length > 0"
          :title="$t('Pick Up Where You Left Off')"
          :description="$t('Continue learning with your ongoing courses. Dive back in and keep making progress toward your goals.')"
          :courses="inProgress"
        />

        <CourseRow
          v-if="latest.length > 0"
          :title="$t('New Courses, Fresh Opportunities')"
          :description="$t('Explore the latest courses added to our library. Stay updated with cutting-edge content and trending topics.')"
          :courses="latest"
          :action="$t('Browse Latest Courses')"
          :url="route('courses', { _query: { sort: 'latest' } })"
        />

        <CourseRow
          v-if="popular.length > 0"
          :title="$t('Most-Loved by Learners')"
          :description="$t('Discover the courses that everyone is talking about. Join the crowd and explore what’s making waves.')"
          :courses="popular"
          :action="$t('Browse Popular Courses')"
          :url="route('courses', { _query: { sort: 'popular', hideCompleted: 'true' } })"
        />

        <CourseRow
          v-if="discover.length > 0"
          :title="$t('Feeling Adventurous?')"
          :description="$t('Browse a surprise selection of courses. Who knows? You might stumble upon your next passion.')"
          :courses="discover"
          :action="$t('Browse All Courses')"
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
