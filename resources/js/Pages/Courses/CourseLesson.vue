<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl leading-tight">{{ courseTitle }}</h2>
    </template>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="flex flex-row">
        <div class="flex-1 relative" style="height: calc(100vh - 8rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto py-6 pr-6">
            <Player
              v-if="video"
              :src="video.url"
              :poster="video.posterImageUrl"
            />

            <div class="flex flex-row justify-between items-center mt-6">
              <h2 class="font-semibold text-xl leading-tight">{{ lessonTitle }}</h2>

              <div class="inline-flex flex-row gap-4">
                <Button variant="outline">Mark Completed</Button>
              </div>
            </div>

            <Tabs v-if="description || resources.length > 0" :default-value="description ? 'description' : 'resources'">
              <TabsList class="mt-6">
                <TabsTrigger v-if="description" value="description">Description</TabsTrigger>
                <TabsTrigger v-if="resources.length > 0" value="resources">Resources</TabsTrigger>
              </TabsList>
              <TabsContent value="description" v-if="description">
                <div class="editor-content" v-html="description"></div>
              </TabsContent>
              <TabsContent value="resources">
                <p>resources</p>
              </TabsContent>
            </Tabs>
          </div>
        </div>

        <div class="w-96 flex-shrink-0 border-x relative" style="height: calc(100vh - 8rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto">
            <p>epizody</p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Player } from '@/Components/Player'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/Tabs'
import { AuthenticatedLayout } from '@/Layouts'
import type { VideoSource } from '@/Types'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/Components/Button'

defineProps<{
  courseTitle: string
  lessonTitle: string
  video: VideoSource | null
  description: string | null
  resources: Array<{}>
}>()
</script>
