<template>
  <div v-if="src" :class="cn('overflow-hidden', imageClasses)" :style="imageStyles">
    <img :src="src" class="w-full h-full object-center">
  </div>
  <div v-else class="bg-accent text-accent-foreground/80 flex items-center justify-center" :class="imageClasses" :style="imageStyles">
    <ImageIcon class="size-4" />
  </div>
</template>

<script setup lang="ts">
import { cn } from '@/Utils'
import { computed } from 'vue'
import { ImageIcon } from 'lucide-vue-next'

const props = defineProps<{
  src: string | null
  width: number
  height: number
  rounding: 'none' | 'rounded' | 'full'
}>()

const imageStyles = computed(() => ({
  width: `${props.width}rem`,
  height: `${props.height}rem`
}))

const imageClasses = computed(() => ({
  'rounded': props.rounding === 'rounded',
  'rounded-full': props.rounding === 'full',
}))
</script>
