<template>
  <slot />
</template>

<script setup lang="ts">
import { useSelectedTheme } from '@/Composables'

useSelectedTheme()
</script>
