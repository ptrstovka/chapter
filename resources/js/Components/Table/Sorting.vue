<template>
  <div class="inline-flex">
    <Button plain @click="toggle" class="h-8 px-1 text-sm" variant="ghost" size="sm">
      <slot />

      <template v-if="isApplied">
        <ChevronsUpIcon v-if="isAsc" class="ml-1.5 w-4 h-4" />
        <ChevronsDownIcon v-else class="ml-1.5 w-4 h-4" />
      </template>
      <ChevronsUpDownIcon v-else class="ml-1.5 w-4 h-4" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ChevronsUpDownIcon, ChevronsUpIcon, ChevronsDownIcon } from "lucide-vue-next";
import { computed } from "vue";
import { Button } from '@/Components/Button'

const props = defineProps<{
  value: string
}>()

const column = defineModel<string | null>('column')
const direction = defineModel<'asc' | 'desc' | null>('direction')

const isApplied = computed(() => column.value === props.value)
const isAsc = computed(() => direction.value === 'asc')

const toggle = () => {
  if (isApplied.value) {
    if (direction.value == 'asc') {
      direction.value = 'desc'
    } else if (direction.value == 'desc') {
      direction.value = null
      column.value = null
    } else {
      direction.value = 'asc'
    }
  } else {
    column.value = props.value
    direction.value = 'asc'
  }
}
</script>
