<template>
  <div :class="cn('border-b py-4 px-4', $attrs.class || '')">
    <slot />
  </div>
</template>
<script setup lang="ts">
import { cn } from '@/Utils'
</script>
