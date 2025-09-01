<template>
  {{ value !== null ? value : '&mdash;'}}
</template>

<script setup lang="ts">
defineProps<{
  value: string | null | number
}>()
</script>
