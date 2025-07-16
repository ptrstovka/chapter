<template>
    <CoursesLayout :page-title="$t('Favorite')" active-tab="favorite">
        <EmptyState
            v-if="favorites.total === 0"
            :title="$t('No favorite courses')"
            :message="$t('You don\'t have any favorite courses.')"
            class="mt-12"
        />

        <div v-else class="grid grid-cols-4 gap-4 mt-4">
            <CourseCard v-for="course in favorites.data" :course="course" />
        </div>

        <div class="flex flex-row justify-end w-full mt-6" v-if="favorites.total > 0">
            <SimplePagination :paginator="favorites" />
        </div>
    </CoursesLayout>
</template>

<script setup lang="ts">
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'
import { Paginator } from '@/Types';
import { SimplePagination } from '@/Components/Pagination';
import { EmptyState } from '@/Components/EmptyState';
import CoursesLayout from './Layouts/CoursesLayout.vue';

const props = defineProps<{
    favorites: Paginator<Course>,
}>()

</script>