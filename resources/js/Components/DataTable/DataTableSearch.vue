<template>
  <div class="relative" v-if="isSearchable">
    <SearchIcon class="w-4 h-4 absolute left-2 top-2 text-muted-foreground" />
    <DebouncedInput :debounce="50" v-model="searchFilter.search" class="h-8 w-[250px] pl-8 pr-8" :placeholder="messages.searchPlaceholder" />
    <button v-if="searchFilter.applied" @click.prevent="searchFilter.reset()" class="absolute right-2 top-2.5 text-muted-foreground hover:text-destructive transition-colors">
      <CircleXIcon class="w-3 h-3" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { injectContext } from './internal'
import messages from './messages'
import { DebouncedInput } from '@/Components/Input'
import { CircleXIcon, SearchIcon } from 'lucide-vue-next'

const { searchFilter, isSearchable } = injectContext()
</script>
