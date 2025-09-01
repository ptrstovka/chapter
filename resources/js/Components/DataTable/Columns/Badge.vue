<template>
  <Badge :variant="variant">{{ text }}</Badge>
</template>

<script setup lang="ts">
import { type BadgeVariants, Badge } from '@/Components/Badge'

defineProps<{
  variant?: BadgeVariants['variant']
  text: string
}>()
</script>
