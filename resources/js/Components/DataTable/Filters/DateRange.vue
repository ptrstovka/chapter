<template>
  <FilterDateRange :title="title" v-model:from="filter[fieldFrom]" v-model:until="filter[fieldUntil]" />
</template>

<script setup lang="ts">
import { type Filter } from '@stacktrace/ui'
import { FilterDateRange } from "@/Components/Filter";

defineProps<{
  title: string
  fieldFrom: string
  fieldUntil: string
  filter: Filter<any>
}>()
</script>
