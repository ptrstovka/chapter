<template>
  <slot />

  <ConfirmationDialog />
</template>

<script setup lang="ts">
import { useSelectedTheme } from '@/Composables'
import { ConfirmationDialog } from '@/Components/ConfirmationDialog'

useSelectedTheme()
</script>
