<template>
  <Primitive v-bind="props">
    <slot />
  </Primitive>
</template>
<script setup lang="ts">
import { Primitive, type PrimitiveProps } from 'reka-ui'

const props = defineProps<PrimitiveProps>()
</script>
