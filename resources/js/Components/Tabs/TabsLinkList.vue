<template>
  <div :class="cn(tabsListVariants({ variant, orientation }), $attrs.class || undefined)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { cn } from '@/Utils'
import { computed } from 'vue'
import { provideTabsContext, tabsListVariants } from '.'

const props = defineProps<{
  variant?: NonNullable<Parameters<typeof tabsListVariants>[0]>['variant']
  orientation?: NonNullable<Parameters<typeof tabsListVariants>[0]>['orientation']
}>()

provideTabsContext(computed(() => ({
  variant: props.variant || 'default',
  orientation: props.orientation || 'horizontal',
})))
</script>
