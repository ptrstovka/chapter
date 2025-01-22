<template>
  <Button v-bind="forwardedProps" as-child>
    <!-- @vue-ignore -->
    <Link :as="props.as || undefined">
      <slot />
    </Link>
  </Button>
</template>

<script setup lang="ts">
import { type InertiaLinkProps, Link } from '@inertiajs/vue3'
import { type ButtonProps, Button } from '@/Components/Button'
import { useForwardProps } from 'radix-vue'
import { computed } from 'vue'

const props = defineProps<ButtonProps & InertiaLinkProps & {
  as?: string
}>()

const delegatedProps = computed(() => {
  const { as: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>
