<template>
  <media-menu-button
    class="transition-colors data-[hocus]:bg-accent data-[hocus]:text-accent-foreground ring-ring parent w-full flex items-center justify-start left-0 z-10 cursor-default select-none rounded-sm text-sm px-2 py-1.5 outline-none"
  >
    <ChevronLeftIcon class="parent-data-[open]:block -ml-0.5 mr-1.5 hidden size-4" />
    <div class="contents parent-data-[open]:hidden">
      <slot name="icon" />
    </div>
    <span class="ml-2 text-foreground parent-data-[open]:ml-1">{{ label }}</span>
    <span class="ml-auto text-sm text-muted-foreground" data-part="hint"></span>
    <ChevronRight class="parent-data-[open]:invisible ml-2 size-4" />
  </media-menu-button>
</template>

<script setup lang="ts">
import { ChevronLeftIcon, ChevronRight } from 'lucide-vue-next'

defineProps<{
  label: string
}>()
</script>
