<template>
  <Head :title="lessonTitle" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl leading-tight">{{ courseTitle }}</h2>
    </template>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="flex flex-row">
        <div class="flex-1 relative" style="height: calc(100vh - 8rem)">
          <div class="absolute inset-0 flex flex-col overflow-y-auto">
            <p>video</p>
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
import { AuthenticatedLayout } from '@/Layouts'
import { Head } from '@inertiajs/vue3'

defineProps<{
  courseTitle: string
  lessonTitle: string
}>()
</script>
