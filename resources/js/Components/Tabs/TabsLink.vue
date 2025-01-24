<script setup lang="ts">
import { Link, type InertiaLinkProps } from '@inertiajs/vue3'
import { cn } from '@/Utils'
import { useBrowserLocation } from '@vueuse/core'
import { computed } from 'vue'

interface Props extends InertiaLinkProps {
  class?: string
  active?: boolean | undefined
}

const props = withDefaults(defineProps<Props>(), {
  active: undefined
})

const location = useBrowserLocation()

const isActive = computed(() => {
  if (props.active !== undefined) {
    return props.active
  }

  if (props.href) {
    return location.value.pathname === new URL(props.href).pathname
  }

  return false
})
</script>

<template>
  <Link
    v-bind="props"
    :data-state="isActive ? 'active' : 'inactive'"
    :class="
      cn(
        'inline-flex items-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow',
        props.class,
      )
    "
  >
    <slot />
  </Link>
</template>
