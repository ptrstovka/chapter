<template>
    <CoursesLayout :page-title="$t('In progress')">
        <EmptyState
            v-if="inProgress.total === 0"
            :title="$t('No courses in progress')"
            :message="$t('You haven\'t started any course yet.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <CourseCard v-for="course in inProgress.data" :course="course" />
        </div>

        <div v-if="inProgress.total > 0" class="flex flex-row justify-center w-full mt-6 xl:justify-end">
            <SimplePagination :paginator="inProgress" />
        </div>
    </CoursesLayout>
</template>

<script setup lang="ts">
import { EmptyState } from '@/Components/EmptyState';
import { CourseCard, type Course } from '@/Components/Course';
import { SimplePagination } from '@/Components/Pagination';
import type { Paginator } from '@/Types';
import CoursesLayout from './Layouts/CoursesLayout.vue';

defineProps<{
    inProgress: Paginator<Course>
}>();
</script>
