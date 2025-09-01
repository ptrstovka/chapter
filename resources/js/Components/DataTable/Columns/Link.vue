<template>
  <component :is="external ? 'a' : Link" :target="target || undefined" :href="href || undefined">{{ value || '&mdash;'}}</component>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
  value: string | null
  href: string | null
  external: boolean
  target: string | null
}>()
</script>
