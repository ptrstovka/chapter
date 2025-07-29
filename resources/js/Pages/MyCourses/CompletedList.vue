<template>
    <CoursesLayout :page-title="$t('Completed')">
        <EmptyState
            v-if="completed.total === 0"
            :title="$t('No completed courses')"
            :message="$t('You haven\'t completed any course yet.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <CourseCard v-for="course in completed.data" :course="course" />
        </div>

        <div v-if="completed.total > 0" class="flex flex-row justify-center w-full mt-6 xl:justify-end">
            <SimplePagination :paginator="completed" />
        </div>
    </CoursesLayout>
</template>

<script setup lang="ts">
import { CourseCard, type Course } from '@/Components/Course';
import { EmptyState } from '@/Components/EmptyState';
import { SimplePagination } from '@/Components/Pagination';
import type { Paginator } from '@/Types';
import CoursesLayout from './Layouts/CoursesLayout.vue';

defineProps<{
    completed: Paginator<Course>,
}>();

</script>