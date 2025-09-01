<template>
  <component :is="as" :as="markRaw(NavigationButton)" :item="item" :tooltip="item.title || undefined">
    <NavigationButtonIcon class="size-4" />
    <span>{{ item.title }}</span>
    <SidebarMenuBadge v-if="item.badge">{{ item.badge }}</SidebarMenuBadge>
    <slot />
  </component>
</template>

<script setup lang="ts">
import SidebarMenuBadge from './SidebarMenuBadge.vue'
import { type Component, markRaw } from 'vue'
import { type NavigationItem } from '@stacktrace/ui'
import { NavigationButton, NavigationButtonIcon } from '@stacktrace/ui'

defineProps<{
  item: NavigationItem
  as: Component
}>()
</script>
