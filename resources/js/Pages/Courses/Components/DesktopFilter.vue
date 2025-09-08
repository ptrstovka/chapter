<template>
  <div>
    <h2 class="font-semibold text-xl leading-tight">{{ $t('Categories') }}</h2>

    <ul class="mt-4 -ml-3 flex flex-col gap-1">
      <li v-for="category in categories">
        <Button
          @click="filter.category = filter.category === category.value ? null : category.value"
          :variant="filter.category == category.value ? 'default' : 'ghost'"
          class="w-full justify-start line-clamp-1 text-left"
          plain
        >{{ category.label }}</Button>
      </li>
      <li>
        <Button @click="filter.category = null" variant="ghost" class="w-full justify-start" plain>{{ $t('All Courses') }}</Button>
      </li>
    </ul>

    <h2 class="font-semibold text-xl leading-tight mt-8 block">{{ $t('Filter') }}</h2>

    <ul class="mt-4 gap-3 flex flex-col">
      <li>
        <CheckboxControl v-model="filter.hideCompleted">{{ $t('Hide Completed') }}</CheckboxControl>
      </li>
      <li>
        <CheckboxControl v-model="filter.onlyFavorite">{{ $t('Only Favorite') }}</CheckboxControl>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { CheckboxControl } from "@/Components/Checkbox";
import type { CourseFilter } from "../Composables/useFilter.ts";
import type { SelectOption } from "@stacktrace/ui";

defineProps<{
  categories: Array<SelectOption>
  filter: CourseFilter
}>()
</script>
