<template>
  <slot />
</template>

<script setup lang="ts">
import { type Context, provideContext } from './internal'

const props = defineProps<{
  context: Context
}>()
provideContext(props.context)
</script>
