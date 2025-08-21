<template>
  <Primitive :as="as || 'li'">
    <a class="bg-secondary hover:bg-muted group transition-colors rounded-md border flex justify-between items-center px-4 py-4" :href="resource.url">
      <div class="flex items-center gap-4">
        <div class="bg-primary text-primary-foreground p-2 rounded-full">
          <FileIcon class="w-5 h-5" />
        </div>

        <div class="inline-flex flex-col">
          <p class="text-sm font-medium">{{ resource.name }}</p>
          <p class="text-xs text-muted-foreground font-mono">{{ resource.size }}</p>
        </div>
      </div>

      <DownloadIcon class="w-4 h-4 text-muted-foreground group-hover:text-primary" />
    </a>
  </Primitive>
</template>

<script setup lang="ts">
import type { Resource } from '../'
import { Primitive } from 'reka-ui'
import { FileIcon, DownloadIcon } from 'lucide-vue-next'

defineProps<{
  as?: string
  resource: Resource
}>()
</script>
