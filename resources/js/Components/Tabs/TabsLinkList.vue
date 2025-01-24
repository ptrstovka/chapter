<script setup lang="ts">
import { cn } from '@/Utils'

const props = defineProps<{
  class?: string
  orientation?: 'horizontal' | 'vertical'
}>()
</script>

<template>
  <div
    v-bind="props"
    :class="
      cn(
        props.orientation === 'vertical' ? 'flex flex-col gap-2 [&>*]:h-9' : 'inline-flex items-center justify-center h-9',
        'rounded-lg bg-muted p-1 text-muted-foreground',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
