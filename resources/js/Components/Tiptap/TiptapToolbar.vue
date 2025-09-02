<template>
  <div class="flex flex-row items-center justify-center border-b border-input py-1 gap-0.5">
    <TooltipProvider>
      <slot />
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { TooltipProvider } from '@/Components/Tooltip'
</script>
