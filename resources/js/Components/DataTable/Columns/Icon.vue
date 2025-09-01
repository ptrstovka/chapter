<template>
  <Icon v-if="src" :src="src" :style="{ width: `${size}rem`, height: `${size}rem`, }" />
</template>

<script setup lang="ts">
import { Icon } from '@stacktrace/ui'

defineProps<{
  src: string | null
  size: number
}>()
</script>
