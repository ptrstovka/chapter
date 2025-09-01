<template>
  <div v-if="table.filter" :class="cn('flex flex-row flex-wrap gap-2 items-center', $attrs.class || '')">
    <template v-for="widget in table.filter.widgets">
      <component
        :is="widget.component"
        v-bind="widget.props"
        :filter="filter"
      />
    </template>

    <FilterResetButton :filter="filter" />
  </div>
</template>

<script setup lang="ts">
import { injectContext } from './internal'
import { FilterResetButton } from '@/Components/Filter'
import { cn } from '@/Utils'

const { table, filter } = injectContext()
</script>
