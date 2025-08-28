<template>
  <Link
    v-bind="props"
    :data-state="isActive ? 'active' : 'inactive'"
    :class="
      cn(
        tabsListItemVariants({
          variant: context?.variant || 'default',
          orientation: context?.orientation || 'horizontal',
        }),
        $attrs.class || undefined,
      )
    "
  >
    <slot />
  </Link>
</template>

<script setup lang="ts">
import { Link, type InertiaLinkProps } from '@inertiajs/vue3'
import { cn } from '@/Utils'
import { computed } from 'vue'
import { useActiveLink } from '@stacktrace/ui'
import { injectTabsContext, tabsListItemVariants } from '.'

const context = injectTabsContext()

interface Props extends InertiaLinkProps {
  active?: boolean | undefined
}

const props = withDefaults(defineProps<Props>(), {
  active: undefined
})

const isLinkActive = useActiveLink(computed(() => {
  const href = props.href

  return { url: typeof href === 'string' ? href : '' }
}))

const isActive = computed(() => props.active !== undefined ? props.active : isLinkActive.value)
</script>
