<template>
  <AlertDialogRoot v-if="control" data-slot="alert-dialog" v-bind="forwarded" v-model:open="control.active.value">
    <slot />
  </AlertDialogRoot>
  <AlertDialogRoot v-else data-slot="alert-dialog" v-bind="forwarded">
    <slot />
  </AlertDialogRoot>
</template>

<script setup lang="ts">
import { type AlertDialogEmits, type AlertDialogProps, AlertDialogRoot, useForwardPropsEmits } from 'reka-ui'
import { type Toggle } from '@stacktrace/ui'

const props = defineProps<AlertDialogProps & {
  control?: Toggle
}>()
const emits = defineEmits<AlertDialogEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>
