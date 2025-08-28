<template>
    <CoursesLayout :page-title="$t('Favorite')">
        <EmptyState
            v-if="favorites.total === 0"
            :title="$t('No favorite courses')"
            :message="$t('You don\'t have any favorite courses.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <CourseCard v-for="course in favorites.data" :course="course" />
        </div>

        <div v-if="favorites.total > 0" class="flex flex-row justify-center w-full mt-6 xl:justify-end">
            <SimplePagination :paginator="favorites" />
        </div>
    </CoursesLayout>
</template>

<script setup lang="ts">
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'
import { type Paginator } from '@/Types';
import { SimplePagination } from '@/Components/Pagination';
import { EmptyState } from '@/Components/EmptyState';
import CoursesLayout from './Layouts/CoursesLayout.vue';

defineProps<{
    favorites: Paginator<Course>,
}>()

</script>
