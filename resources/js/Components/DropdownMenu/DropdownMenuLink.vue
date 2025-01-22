<template>
  <DropdownMenuItem v-bind="forwardedProps" as-child>
    <!-- @vue-ignore -->
    <Link :as="props.as || undefined" class="w-full">
      <slot />
    </Link>
  </DropdownMenuItem>
</template>

<script setup lang="ts">
import { type DropdownMenuItemProps, useForwardProps } from 'radix-vue'
import { computed, type HTMLAttributes } from 'vue'
import { Link, type InertiaLinkProps } from '@inertiajs/vue3'
import { DropdownMenuItem } from './'

const props = defineProps<DropdownMenuItemProps & InertiaLinkProps & {
  class?: HTMLAttributes['class']
  inset?: boolean
  as?: string
}>()

const delegatedProps = computed(() => {
  const { as: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>
