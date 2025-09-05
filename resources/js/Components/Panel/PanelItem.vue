<template>
  <div :class="cn('py-4 px-4', $attrs.class || '')">
    <div v-if="label" class="flex flex-col sm:flex-row">
      <div class="sm:w-2/5 font-medium text-sm" :class="{ 'sm:mt-1.5': !description }">
        <span>{{ label }}</span>
        <p class="text-muted-foreground" v-if="description">{{ description }}</p>
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

defineProps<{
  label?: string | null | undefined
  description?: string | null | undefined
}>()
</script>
