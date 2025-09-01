<template>
  <Separator
    data-slot="sidebar-separator"
    data-sidebar="separator"
    :class="cn('bg-sidebar-border mx-2 w-auto', props.class)"
  >
    <slot />
  </Separator>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils'
import { Separator } from '@/Components/Separator'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>
