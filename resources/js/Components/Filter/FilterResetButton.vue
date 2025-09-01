<template>
  <Button class="h-8" @click="filter.reset()" v-if="filter.applied" variant="ghost">Reset <XIcon class="ml-2 mt-0.5 w-4 h-4" /></Button>
</template>

<script setup lang="ts">
import type { Filter } from "@stacktrace/ui";
import { XIcon } from "lucide-vue-next";
import { Button } from '@/Components/Button'

defineProps<{
  filter: Filter<any>
}>()
</script>
