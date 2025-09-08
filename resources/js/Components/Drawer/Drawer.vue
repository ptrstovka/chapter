<template>
  <DrawerRoot v-if="control" data-slot="drawer" v-bind="forwarded" v-model:open="control.active.value">
    <slot />
  </DrawerRoot>
  <DrawerRoot v-else data-slot="drawer" v-bind="forwarded">
    <slot />
  </DrawerRoot>
</template>

<script lang="ts" setup>
import type { Toggle } from "@stacktrace/ui";
import { reactiveOmit } from "@vueuse/core";
import type { DrawerRootEmits, DrawerRootProps } from 'vaul-vue'
import { useForwardPropsEmits } from 'reka-ui'
import { DrawerRoot } from 'vaul-vue'

const props = withDefaults(defineProps<DrawerRootProps & {
  control?: Toggle
}>(), {
  shouldScaleBackground: true,
})

const emits = defineEmits<DrawerRootEmits>()

const delegated = reactiveOmit(props, 'control')
const forwarded = useForwardPropsEmits(delegated, emits)
</script>
