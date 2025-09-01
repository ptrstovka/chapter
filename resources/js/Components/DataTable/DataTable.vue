<template>
  <div>
    <DataTableProvider :context="context">
      <!-- Empty Table -->
      <DataTableEmpty
        v-if="table.isEmpty"
        :title="emptyTableMessage || messages.emptyTableTitle"
        :description="emptyTableDescription || messages.emptyTableDescription"
        :icon="TableIcon"
      >
        <slot name="empty-table" />
      </DataTableEmpty>

      <!-- Data Table -->
      <template v-else>
        <div
          class="flex flex-row justify-between"
          :class="cn(insetLeft || 'pl-2', insetRight || 'pr-2', { 'border-b py-2': ! borderless, 'pb-3': borderless })"
        >
          <div class="inline-flex items-center gap-4">
            <slot name="search" />

            <DataTableSearch />
          </div>

          <div class="flex flex-row items-center gap-2">
            <div v-if="somethingSelected" class="text-sm font-medium mr-4">{{ messages.selectedRows(selectableRows.selectedCount.value, selectableRows.totalCount.value) }}</div>

            <DataTableBulkActions @event="onEvent($event.name, $event.selection)" />

            <DataTableClearSelectionButton />

            <slot name="actions" />

            <DataTableViewSettings />
          </div>
        </div>

        <DataTableFilter :class="cn(insetLeft || 'pl-2', insetRight || 'pr-2', { 'border-b py-2': ! borderless, 'pb-4': borderless })" />

        <Table v-if="table.rows.length">
          <TableHeader>
            <TableRow>
              <TableHead class="w-10 text-center" :class="cn(insetLeft || '')">
                <BulkSelect :disabled="! hasBulkActions" :selectable="selectableRows" />
              </TableHead>
              <template v-for="(heading, idx) in headings">
                <TableHead
                  :class="cn({
                      'px-0': !!heading.sortableAs,
                    }, createHeadingStyle(heading.style), !hasRowActions && idx + 1 == headings.length ? (insetRight || '') : '')"
                  :style="{
                      width: heading.width || undefined,
                      minWidth: heading.minWidth || undefined,
                      maxWidth: heading.maxWidth || undefined,
                    }"
                >
                  <Sorting v-if="heading.sortableAs" :value="heading.sortableAs" v-model:column="sortFilter.sort_by" v-model:direction="sortFilter.sort_direction">{{ heading.name }}</Sorting>
                  <template v-else>{{ heading.name }}</template>
                </TableHead>
              </template>
              <TableHead class="w-10" v-if="hasRowActions" :class="cn(insetRight || '')"/>
            </TableRow>
          </TableHeader>
          <TableBody>
            <SelectableTableRow v-for="row in rows" :class="cn(createRowStyle({ highlight: row.highlightAs || 'default' }))" :value="row.key" v-model="selectableRows.selection.value" :disabled="! shouldShowCheckboxForRow(row)">
              <TableCell class="text-center" :class="cn(insetLeft || '')">
                <RowSelect />
              </TableCell>

              <template v-for="(cell, idx) in row.cells">
                <DataTableCell :cell="cell" :class="!hasRowActions && idx + 1 == row.cells.length ? (insetRight || '') : ''" />
              </template>
              <TableCell v-if="hasRowActions" class="py-0.5" :class="cn(insetRight || '')">
                <DataTableRowActions :row="row" @event="onEvent($event.name, $event.selection)" />
              </TableCell>
            </SelectableTableRow>
          </TableBody>
          <TableFooter v-if="table.footerCells.length > 0">
            <TableRow>
              <TableCell />
              <template v-for="cell in table.footerCells">
                <DataTableCell v-if="cell" :cell="cell" />
                <TableCell v-else />
              </template>
              <TableCell v-if="hasRowActions" />
            </TableRow>
          </TableFooter>
        </Table>

        <DataTablePagination :class="cn(insetLeft || 'pl-4', insetRight || 'pr-4')" />

        <DataTableEmpty
          v-if="table.rows.length === 0"
          :title="emptyResultsMessage || messages.searchEmptyTitle"
          :description="emptyResultsDescription || messages.searchEmptyDescription"
          :icon="SearchIcon"
        >
          <Button class="mt-6" @click="clearSearch"><XIcon class="w-4 h-4 mr-2" /> {{ messages.clearSearch }}</Button>

          <slot name="empty-results" />
        </DataTableEmpty>
      </template>
    </DataTableProvider>
  </div>
</template>

<script setup lang="ts">
import { type DataTableValue } from "./";
import { createContext } from './internal'
import { createHeadingStyle, createRowStyle } from '.'
import { computed } from "vue";
import { cn } from "@/Utils";
import { SearchIcon, XIcon, TableIcon } from 'lucide-vue-next'
import { Button } from '@/Components/Button'
import { Table, TableBody, TableCell, TableRow, TableHead, SelectableTableRow, RowSelect, Sorting, TableHeader, TableFooter, BulkSelect } from '@/Components/Table'
import messages from './messages'
import DataTableCell from './DataTableCell.vue'
import DataTableProvider from './DataTableProvider.vue'
import DataTableRowActions from './DataTableRowActions.vue'
import DataTableBulkActions from './DataTableBulkActions.vue'
import DataTableSearch from './DataTableSearch.vue'
import DataTableClearSelectionButton from './DataTableClearSelectionButton.vue'
import DataTableViewSettings from './DataTableViewSettings.vue'
import DataTableFilter from './DataTableFilter.vue'
import DataTablePagination from './DataTablePagination.vue'
import DataTableEmpty from './DataTableEmpty.vue'

const emit = defineEmits() // eslint-disable-line vue/valid-define-emits

const props = defineProps<{
  table: DataTableValue
  emptyTableMessage?: string | null | undefined
  emptyTableDescription?: string | null | undefined
  emptyResultsMessage?: string | null | undefined
  emptyResultsDescription?: string | null | undefined
  borderless?: boolean
  insetLeft?: string | undefined
  insetRight?: string | undefined
}>()

const context = createContext(computed(() => props.table))

const {
  rows,
  headings,

  clearSearch,
  sortFilter,

  shouldShowCheckboxForRow,
  selectableRows,
  somethingSelected,

  hasRowActions,
  hasBulkActions,
} = context

// Called when either row action or bulk action is triggered.
const onEvent = (name: string, selection: Array<number | string>) => {
  emit(name, selection)
}
</script>
