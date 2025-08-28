<template>
  <TabsTrigger
    data-slot="tabs-trigger"
    v-bind="forwardedProps"
    :class="cn(
      tabsListItemVariants({
        variant: context?.variant || 'default',
        orientation: context?.orientation || 'horizontal',
      }),
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { TabsTrigger, type TabsTriggerProps, useForwardProps } from 'reka-ui'
import { cn } from '@/Utils'
import { injectTabsContext, tabsListItemVariants } from '.'

const context = injectTabsContext()

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const forwardedProps = useForwardProps(delegatedProps)
</script>
