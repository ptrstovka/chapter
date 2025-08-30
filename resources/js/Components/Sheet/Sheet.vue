<script setup lang="ts">
import { DialogRoot, type DialogRootEmits, type DialogRootProps, useForwardPropsEmits } from 'reka-ui'
import { type Toggle } from '@stacktrace/ui'

const props = defineProps<DialogRootProps & {
  control?: Toggle
}>()
const emits = defineEmits<DialogRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DialogRoot v-if="control" data-slot="sheet" v-bind="forwarded" v-model:open="control.active.value">
    <slot />
  </DialogRoot>
  <DialogRoot v-else data-slot="sheet" v-bind="forwarded">
    <slot />
  </DialogRoot>
</template>
