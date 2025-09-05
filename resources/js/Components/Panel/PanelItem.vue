<template>
  <div :class="cn('py-4 px-4', $attrs.class || '')">
    <div v-if="label" :class="cn('flex', { 'sm:items-center': center, 'items-center': !wrap && center }, wrap ? 'flex-col sm:flex-row' : 'flex-row')">
      <div class="font-medium text-sm" :class="cn({ 'sm:mt-1.5': !description }, wrap ? 'sm:w-2/5' : 'flex-1')">
        <span>{{ label }}</span>
        <p class="text-xs text-muted-foreground w-3/4" v-if="description">{{ description }}</p>
      </div>
      <div class="mt-0.5 sm:mt-0 sm:w-3/5 text-foreground font-medium text-sm">
        <slot />
      </div>
    </div>

    <slot v-else />
  </div>
</template>

<script setup lang="ts">
import { cn } from "@/Utils";

withDefaults(defineProps<{
  label?: string | null | undefined
  description?: string | null | undefined
  center?: boolean
  wrap?: boolean
}>(), {
  center: false,
  wrap: true,
})
</script>
