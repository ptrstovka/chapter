<template>
  <div class="flex min-w-0 items-center gap-3 py-1.5 sm:gap-4">
    <div class="min-w-0 flex-1">
      <p v-if="courseTitle" class="truncate text-sm font-semibold">{{ courseTitle }}</p>
      <p class="text-xs font-medium text-muted-foreground">{{ preview.versionLabel }}</p>
    </div>
    <div class="flex shrink-0 items-center gap-2">
      <slot />
      <LinkButton :href="preview.studioUrl" variant="outline">
        {{ preview.returnLabel }}
      </LinkButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { LinkButton } from '@/Components/Button'
import type { CoursePreview } from '@/Pages/Courses'

defineProps<{
  preview: CoursePreview
  courseTitle?: string
}>()
</script>
