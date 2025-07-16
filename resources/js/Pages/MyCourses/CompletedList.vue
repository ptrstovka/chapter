<template>
    <CoursesLayout :page-title="$t('Completed')" active-tab="completed">
        <EmptyState
            v-if="completed.total === 0"
            :title="$t('No completed courses')"
            :message="$t('You haven\'t completed any course yet.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-4 gap-4 mt-4">
            <CourseCard v-for="course in completed.data" :course="course" />
        </div>

        <div class="flex flex-row justify-end w-full mt-6" v-if="completed.total > 0">
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

const props = defineProps<{
    completed: Paginator<Course>,
}>();

</script>