<template>
  <NavigationButton
    :item="item"
    :class="
      cn(
         tabsListItemVariants({
          variant: context?.variant || 'default',
          orientation: context?.orientation || 'horizontal',
        }),
        props.class,
      )"
    >
    {{ item.title }}
  </NavigationButton>
</template>

<script setup lang="ts">
import { cn } from '@/Utils'
import { type NavigationItem, NavigationButton } from '@stacktrace/ui'
import { type HTMLAttributes } from 'vue'
import { injectTabsContext, tabsListItemVariants } from '.'

const context = injectTabsContext()

const props = defineProps<{
  item: NavigationItem
  class?: HTMLAttributes['class']
}>()
</script>
