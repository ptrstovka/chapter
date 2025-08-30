<template>
  <BreadcrumbList>
    <template v-for="(item, idx) in navigation">
      <BreadcrumbItem>
        <BreadcrumbLink
          v-if="item.action && isLinkAction(item.action)"
          :href="item.action.url"
          :as="item.action.external ? 'a' : undefined"
        >
          {{ item.title }}
        </BreadcrumbLink>
        <BreadcrumbPage v-else-if="idx === list.length - 1">{{ item.title }}</BreadcrumbPage>
        <template v-else>{{ item.title }}</template>
      </BreadcrumbItem>
      <BreadcrumbSeparator v-if="idx < list.length - 1" />
    </template>
  </BreadcrumbList>
</template>

<script setup lang="ts">
import { type Menu, useNavigation, isLinkAction } from '@stacktrace/ui'
import { computed } from 'vue'
import BreadcrumbList from './BreadcrumbList.vue'
import BreadcrumbItem from './BreadcrumbItem.vue'
import BreadcrumbLink from './BreadcrumbLink.vue'
import BreadcrumbPage from './BreadcrumbPage.vue'
import BreadcrumbSeparator from './BreadcrumbSeparator.vue'

const props = defineProps<{
  list: Menu
}>()

const navigation = useNavigation(computed(() => props.list))
</script>
