<script setup lang="ts">
import { type AlertDialogEmits, type AlertDialogProps, AlertDialogRoot, useForwardPropsEmits } from 'radix-vue'
import { type Toggle } from '@stacktrace/ui'

const props = defineProps<AlertDialogProps & {
  control: Toggle
}>()
const emits = defineEmits<AlertDialogEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <AlertDialogRoot v-if="control" v-model:open="control.active.value">
    <slot />
  </AlertDialogRoot>
  <AlertDialogRoot v-else v-bind="forwarded">
    <slot />
  </AlertDialogRoot>
</template>
