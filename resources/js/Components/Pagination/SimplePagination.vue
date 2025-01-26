<template>
  <div class="inline-flex flex-row gap-2 items-center">
    <Button class="px-2" :as="paginator.firstPageUrl ? Link : undefined" :disabled="!paginator.firstPageUrl" :href="paginator.firstPageUrl || undefined" variant="outline">
      <ChevronsLeftIcon class="w-4 h-4" />
    </Button>
    <Button class="px-2" :as="paginator.prevPageUrl ? Link : undefined" :disabled="!paginator.prevPageUrl" :href="paginator.prevPageUrl || undefined" variant="outline">
      <ChevronLeftIcon class="w-4 h-4" />
    </Button>
    <span class="px-2 text-sm font-semibold">{{ paginator.currentPage }} <span class="text-foreground/60 font-normal">of</span> {{ paginator.lastPage }}</span>
    <Button class="px-2" :as="paginator.nextPageUrl ? Link : undefined" :disabled="!paginator.nextPageUrl" :href="paginator.nextPageUrl || undefined" variant="outline">
      <ChevronRightIcon class="w-4 h-4" />
    </Button>
    <Button class="px-2" :as="paginator.lastPageUrl ? Link : undefined" :disabled="!paginator.lastPageUrl" :href="paginator.lastPageUrl || undefined" variant="outline">
      <ChevronsRightIcon class="w-4 h-4" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import type { Paginator } from '@/Types'
import { ChevronsLeftIcon, ChevronLeftIcon, ChevronsRightIcon, ChevronRightIcon } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/Components/Button'

defineProps<{
  paginator: Paginator<any>
}>()
</script>
