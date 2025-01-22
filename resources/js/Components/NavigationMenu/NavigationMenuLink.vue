<script setup lang="ts">
import {
  NavigationMenuLink,
  type NavigationMenuLinkEmits,
  type NavigationMenuLinkProps,
  useForwardPropsEmits,
} from 'radix-vue'
import type { Component } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<NavigationMenuLinkProps & {
  as?: string | Component
}>(), {
  as: Link
})
const emits = defineEmits<NavigationMenuLinkEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <NavigationMenuLink v-bind="forwarded">
    <slot />
  </NavigationMenuLink>
</template>
