<template>
  <Button v-bind="forwardedProps" as-child plain>
    <Link :as="props.as || undefined" :href="href">
      <slot />
    </Link>
  </Button>
</template>

<script setup lang="ts">
import { type InertiaLinkProps, Link } from '@inertiajs/vue3'
import { type ButtonProps, Button } from '.'
import { useForwardProps } from 'reka-ui'
import { computed } from 'vue'

const props = defineProps<ButtonProps & InertiaLinkProps & {
  as?: string
  href: string
}>()

const delegatedProps = computed(() => {
  const { as: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>
