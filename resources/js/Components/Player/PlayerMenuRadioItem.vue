<template>
  <media-radio
    class="data-[hocus]:bg-accent data-[hocus]:text-accent-foreground data-[focus]:ring-[3px] ring-ring group relative flex w-full cursor-default select-none items-center justify-start rounded-sm px-2 py-1.5 text-sm outline-none transition-colors"
    :value="value"
  >
    <DotIcon class="size-4 fill-current invisible group-data-[checked]:visible" />
    <span class="ml-2">
      <slot />
    </span>
  </media-radio>
</template>

<script setup lang="ts">
import { DotIcon } from 'lucide-vue-next'

defineProps<{
  value?: string
}>()
</script>
