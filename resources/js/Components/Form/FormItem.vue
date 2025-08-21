<template>
  <div :class="cn('space-y-2', props.class)">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { type HTMLAttributes } from 'vue'
import { cn } from '@/Utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>
