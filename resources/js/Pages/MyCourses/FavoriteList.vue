<template>
    <Head :title="$t('Favorite')" />

    <AuthenticatedLayout class="bg-background">
        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4">
                    <div class="w-72">
                        <h2 class="text-xl font-semibold leading-tight">{{ $t('My Courses') }}</h2>
                    </div>

                    <div class="w-full">
                        <div class="flex gap-1.5">
                            <TabsLinkList class="flex gap-1">
                                <TabsLink :href="route('mycourses')">{{ $t('In progress') }}</TabsLink>
                                <TabsLink :href="route('mycourses.favorite')">{{ $t('Favorite') }}</TabsLink>
                                <TabsLink :href="route('mycourses.completed')">{{ $t('Completed') }}</TabsLink>
                            </TabsLinkList>
                        </div>

                        <EmptyState
                            v-if="favorites.total === 0"
                            :title="$t('No favorite courses')"
                            :message="$t('You don\'t have any favorite courses.')"
                            class="mt-12"
                        />

                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <CourseCard v-for="course in favorites.data" :course="course" />
                        </div>

                        <div class="flex flex-row justify-end w-full mt-6" v-if="favorites.total > 0">
                            <SimplePagination :paginator="favorites" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { TabsLink, TabsLinkList } from '@/Components/Tabs';
import { AuthenticatedLayout } from '@/Layouts';
import { Head } from '@inertiajs/vue3';
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'
import { Paginator } from '@/Types';
import { SimplePagination } from '@/Components/Pagination';
import { EmptyState } from '@/Components/EmptyState';

const props = defineProps<{
    favorites: Paginator<Course>,
}>()

</script>