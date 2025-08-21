<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      tabsListVariants({
        variant: context?.variant || 'default',
        orientation: context?.orientation || 'horizontal',
      }),
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { TabsList, type TabsListProps } from 'reka-ui'
import { cn } from '@/Utils'
import { injectTabsContext, tabsListVariants } from '.'

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')

const context = injectTabsContext()
</script>
