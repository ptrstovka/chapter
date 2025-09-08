<template>
  <div>
    <Select v-model="filter.sort">
      <SelectTrigger class="w-fit" size="sm">
        <ArrowDownAZIcon />

        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="option in options" :value="option.value">{{ option.label }}</SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>

<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/Select";
import type { CourseFilter } from "../Composables/useFilter.ts";
import type { SelectOption } from "@stacktrace/ui";
import { ArrowDownAZIcon } from 'lucide-vue-next'

defineProps<{
  options: Array<SelectOption>
  filter: CourseFilter
}>()
</script>
