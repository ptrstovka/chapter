<template>
  <TableCell
    :class="cn(createCellStyle(cell.style), $attrs.class || '')"
    :style="{
      width: cell.width || undefined,
      minWidth: cell.minWidth || undefined,
      maxWidth: cell.maxWidth || undefined,
    }"
  >
    <Primitive v-if="cell.link" :as="cell.link.isExternal ? 'a' : Link" :href="cell.link.url">
      <component :is="cell.component" v-bind="cell.props" />
    </Primitive>
    <component v-else :is="cell.component" v-bind="cell.props" />
  </TableCell>
</template>

<script setup lang="ts">
import { type Cell } from './internal'
import { cn } from "@/Utils";
import { Link } from "@inertiajs/vue3";
import { Primitive } from "reka-ui";
import { TableCell } from "@/Components/Table";
import { createCellStyle } from '.'

defineProps<{
  cell: Cell
}>()
</script>
