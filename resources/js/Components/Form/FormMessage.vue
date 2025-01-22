<script lang="ts" setup>
defineProps<{
  message: string
}>()
</script>

<template>
  <p class="text-[0.8rem] font-medium text-destructive">{{ message }}</p>
</template>
