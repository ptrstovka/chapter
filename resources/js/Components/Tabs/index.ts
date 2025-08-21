import type { MaybeRefOrGetter } from '@vueuse/core'
import { cva, type VariantProps } from 'class-variance-authority'
import { computed, type ComputedRef, inject, provide, toValue } from 'vue'

export { default as Tabs } from './Tabs.vue'
export { default as TabsContent } from './TabsContent.vue'
export { default as TabsLink } from './TabsLink.vue'
export { default as TabsLinkList } from './TabsLinkList.vue'
export { default as TabsList } from './TabsList.vue'
export { default as TabsNavigation } from './TabsNavigation.vue'
export { default as TabsTrigger } from './TabsTrigger.vue'

export const tabsListVariants = cva(
  'rounded-lg p-1',
  {
    variants: {
      variant: {
        default: 'bg-muted text-muted-foreground',
        ghost: 'bg-transparent gap-1 p-0',
      },
      orientation: {
        vertical: 'flex flex-col gap-1',
        horizontal: 'inline-flex items-center justify-center w-fit',
      }
    },
    defaultVariants: {
      variant: 'default',
      orientation: 'horizontal',
    }
  }
)
export type TabsListVariants = VariantProps<typeof tabsListVariants>

export const tabsListItemVariants = cva(
  'inline-flex items-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-ring focus-visible:ring-[3px] focus-visible:outline-1 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4',
  {
    variants: {
      variant: {
        default: 'data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm dark:data-[state=active]:border-input dark:data-[state=active]:bg-input/30 text-foreground dark:text-muted-foreground',
        ghost: 'hover:bg-accent py-2 hover:text-accent-foreground dark:hover:bg-accent/50 data-[state=active]:bg-muted data-[state=active]:text-foreground',
      },
      orientation: {
        vertical: 'px-4 py-2',
        horizontal: '',
      }
    },
    defaultVariants: {
      variant: 'default',
      orientation: 'horizontal',
    }
  }
)
export type TabsListItemVariants = VariantProps<typeof tabsListItemVariants>
export type TabsVariants = TabsListVariants | TabsListItemVariants

const TabsContextKey = Symbol()
interface TabsContext {
  variant: NonNullable<Parameters<typeof tabsListVariants>[0]>['variant']
  orientation: NonNullable<Parameters<typeof tabsListVariants>[0]>['orientation']
}
export function provideTabsContext(context: MaybeRefOrGetter<TabsContext>) {
  provide(TabsContextKey, context)
}
export function injectTabsContext(): ComputedRef<TabsContext | null> {
  const value = inject<TabsContext>(TabsContextKey)

  return computed(() => value ? toValue(value) : null)
}
