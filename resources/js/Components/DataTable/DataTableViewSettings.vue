<template>
  <DropdownMenu v-if="hasPerPageSettings">
    <DropdownMenuTrigger as-child>
      <Button variant="outline" size="sm"><SlidersHorizontalIcon class="w-4 h-4 mr-2" /> {{ messages.viewOptions }}</Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <template v-if="table.perPageOptions.length > 0">
        <DropdownMenuLabel>{{ messages.perPage }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem
          v-for="option in table.perPageOptions"
          @select="setPerPage(option)"
          :model-value="`${paginationFilter.limit}` == `${option}` || (option == table.defaultPerPage && !paginationFilter.limit)"
        >{{ messages.perPageOption(option) }}</DropdownMenuCheckboxItem>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import messages from './messages'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/Components/DropdownMenu'
import { SlidersHorizontalIcon } from 'lucide-vue-next'
import { injectContext } from './internal'

const { hasPerPageSettings, setPerPage, table, paginationFilter } = injectContext()
</script>
