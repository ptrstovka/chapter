<template>
  <TabsLinkList v-if="navigation.length > 0" :orientation="orientation" :variant="variant">
    <TabsNavigationItem v-for="item in navigation" :item="item" />
  </TabsLinkList>
</template>

<script setup lang="ts">
import { tabsListVariants } from '.'
import { computed } from 'vue'
import TabsLinkList from './TabsLinkList.vue'
import TabsNavigationItem from './TabsNavigationItem.vue'
import { type Menu, useNavigation } from '@stacktrace/ui'

const props = defineProps<{
  menu: Menu
  variant?: NonNullable<Parameters<typeof tabsListVariants>[0]>['variant']
  orientation?: NonNullable<Parameters<typeof tabsListVariants>[0]>['orientation']
}>()

const navigation = useNavigation(computed(() => props.menu))
</script>
