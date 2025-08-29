<template>
  <div :class="cn('overflow-hidden flex items-center flex-col', $attrs.class || '')">
    <div class="flex flex-col max-w-lg items-center relative">
      <EmptyPattern class="text-foreground/10 h-[520px] -translate-y-2/3 absolute" />

      <div class="w-16 h-16 rounded-full bg-muted text-foreground inline-flex items-center justify-center mb-8">
        <TvIcon class="w-6 h-6" />
      </div>

      <p class="font-semibold text-center">{{ title }}</p>
      <p class="max-w-md text-center text-muted-foreground text-sm mt-1">{{ message }}</p>

      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { cn } from '@/Utils'
import { EmptyPattern } from '.'
import { TvIcon } from 'lucide-vue-next'

defineProps<{
  title: string
  message: string
}>()
</script>
