<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout class="bg-background">

        <div class="py-8">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4">
                    <div>
                        <h2 class="text-xl font-semibold leading-tight">{{ $t('My Courses') }}</h2>
                    </div>

                    <div class="w-full">
                        <div class="flex flex-wrap items-center gap-4 sm:gap-4">
                            <TabsLinkList class="flex flex-wrap gap-2">
                                <TabsLink :href="route('mycourses.inprogress')" :active="route().current('mycourses.inprogress')">
                                    {{ $t('In progress') }}
                                </TabsLink>
                                <TabsLink :href="route('mycourses.favorite')" :active="route().current('mycourses.favorite')">
                                    {{ $t('Favorite') }}
                                </TabsLink>
                                <TabsLink :href="route('mycourses.completed')" :active="route().current('mycourses.completed')">
                                    {{ $t('Completed') }}
                                </TabsLink>
                            </TabsLinkList>
                        </div>

                        <slot />
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

defineProps<{
    pageTitle: string,
}>();
</script>
