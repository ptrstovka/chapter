<template>
    <Head :title="$t('Completed')" />

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
                            v-if="completed.total === 0"
                            :title="$t('No completed courses')"
                            :message="$t('You haven\'t completed any course yet.')"
                            class="mt-12"
                        />

                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <CourseCard v-for="course in completed.data" :course="course" />
                        </div>

                        <div class="flex flex-row justify-end w-full mt-6" v-if="completed.total > 0">
                            <SimplePagination :paginator="completed" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { CourseCard, type Course } from '@/Components/Course';
import { EmptyState } from '@/Components/EmptyState';
import { SimplePagination } from '@/Components/Pagination';
import { TabsLink, TabsLinkList } from '@/Components/Tabs';
import { AuthenticatedLayout } from '@/Layouts';
import { Paginator } from '@/Types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    completed: Paginator<Course>,
}>();

</script>