<template>
  <template v-if="table.rows.length > 0">
    <div :class="cn('border-t py-2 flex justify-between items-center w-full', $attrs.class || '')" v-if="table.pagination">
      <span class="pr-2 text-sm font-semibold"><span class="text-foreground/60 font-normal">{{ messages.paginatorTotal }}</span> {{ table.pagination.total }}</span>

      <div class="flex flex-row gap-2 items-center">
        <Button class="px-2" :as="table.pagination.firstPageUrl ? Link : undefined" :disabled="!table.pagination.firstPageUrl" :href="table.pagination.firstPageUrl || undefined" variant="outline">
          <ChevronsLeftIcon class="w-4 h-4" />
        </Button>
        <Button class="px-2" :as="table.pagination.prevPageUrl ? Link : undefined" :disabled="!table.pagination.prevPageUrl" :href="table.pagination.prevPageUrl || undefined" variant="outline">
          <ChevronLeftIcon class="w-4 h-4" />
        </Button>
        <span class="px-2 text-sm font-semibold">{{ table.pagination.currentPage }} <span class="text-foreground/60 font-normal">{{ messages.paginatorOf }}</span> {{ table.pagination.lastPage }}</span>
        <Button class="px-2" :as="table.pagination.nextPageUrl ? Link : undefined" :disabled="!table.pagination.nextPageUrl" :href="table.pagination.nextPageUrl || undefined" variant="outline">
          <ChevronRightIcon class="w-4 h-4" />
        </Button>
        <Button class="px-2" :as="table.pagination.lastPageUrl ? Link : undefined" :disabled="!table.pagination.lastPageUrl" :href="table.pagination.lastPageUrl || undefined" variant="outline">
          <ChevronsRightIcon class="w-4 h-4" />
        </Button>
      </div>
    </div>
    <div v-else-if="table.cursorPagination" :class="cn('border-t py-2 flex justify-end items-center w-full', $attrs.class || '')">
      <div class="flex flex-row gap-2 items-center">
        <Button class="px-2 inline-flex gap-2" :as="table.cursorPagination.prevPageUrl ? Link : undefined" :disabled="!table.cursorPagination.prevPageUrl" :href="table.cursorPagination.prevPageUrl || undefined" variant="outline">
          <ChevronLeftIcon class="w-4 h-4" />
          {{ messages.paginatorPrevious }}
        </Button>
        <Button class="px-2 inline-flex gap-2" :as="table.cursorPagination.nextPageUrl ? Link : undefined" :disabled="!table.cursorPagination.nextPageUrl" :href="table.cursorPagination.nextPageUrl || undefined" variant="outline">
          {{ messages.paginatorNext }}
          <ChevronRightIcon class="w-4 h-4" />
        </Button>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { injectContext } from './internal'
import messages from './messages'
import { cn } from '@/Utils'
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon, ChevronsLeftIcon, ChevronsRightIcon } from 'lucide-vue-next'

const { table } = injectContext()
</script>
