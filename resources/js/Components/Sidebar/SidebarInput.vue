<template>
  <Input
    data-slot="sidebar-input"
    data-sidebar="input"
    :class="cn(
      'bg-background h-8 w-full shadow-none',
      props.class,
    )"
  >
    <slot />
  </Input>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils'
import { Input } from '@/Components/Input'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>
