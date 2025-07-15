<script setup lang="ts">
import { TabsLink, TabsLinkList } from '@/Components/Tabs';
import { AuthenticatedLayout } from '@/Layouts';
import { Head } from '@inertiajs/vue3';

</script>

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
                                <TabsLink :href="route('mycourses')">{{ $t('InProgress') }}</TabsLink>
                                <TabsLink :href="route('mycourses.favorite')">{{ $t('Favorite') }}</TabsLink>
                                <TabsLink :href="route('mycourses.completed')">{{ $t('Completed') }}</TabsLink>
                            </TabsLinkList>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>