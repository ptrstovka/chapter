<template>
    <CoursesLayout :page-title="$t('In progress')" active-tab="inProgress">
        <EmptyState
            v-if="inProgress.total === 0"
            :title="$t('No courses in progress')"
            :message="$t('You haven\'t started any course yet.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-4 gap-4 mt-4">
            <CourseCard v-for="course in inProgress.data" :course="course" />
        </div>

        <div class="flex flex-row justify-end w-full mt-6" v-if="inProgress.total > 0">
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

const props = defineProps<{
    inProgress: Paginator<Course>
}>();
</script>
