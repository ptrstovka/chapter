<template>
  <TabsRoot
    data-slot="tabs"
    v-bind="forwarded"
    :class="cn('flex flex-col gap-2', props.class)"
  >
    <slot />
  </TabsRoot>
</template>

<script setup lang="ts">
import { provideTabsContext, tabsListVariants } from '.'
import type { TabsRootEmits, TabsRootProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { TabsRoot, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/Utils'

const props = defineProps<TabsRootProps & {
  class?: HTMLAttributes['class']
  variant?: NonNullable<Parameters<typeof tabsListVariants>[0]>['variant']
}>()
const emits = defineEmits<TabsRootEmits>()

const delegatedProps = reactiveOmit(props, 'class')
const forwarded = useForwardPropsEmits(delegatedProps, emits)

provideTabsContext(computed(() => ({
  variant: props.variant || 'default',
  orientation: 'horizontal',
})))
</script>
