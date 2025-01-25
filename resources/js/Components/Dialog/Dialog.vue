<script setup lang="ts">
import { DialogRoot, type DialogRootEmits, type DialogRootProps, useForwardPropsEmits } from 'radix-vue'
import type { Toggle } from "@/Composables";

const props = defineProps<DialogRootProps & {
  control?: Toggle
}>()
const emits = defineEmits<DialogRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DialogRoot v-if="control" v-bind="forwarded" v-model:open="control.active.value">
    <slot />
  </DialogRoot>
  <DialogRoot v-else v-bind="forwarded">
    <slot />
  </DialogRoot>
</template>
