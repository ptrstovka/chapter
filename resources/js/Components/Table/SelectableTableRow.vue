<template>
<TableRow v-bind="$attrs" :data-state="isSelected && 'selected'">
  <slot />
</TableRow>
</template>

<script setup lang="ts">
import { useVModel } from "@vueuse/core";
import { computed, provide } from "vue";
import { TableRow } from './'

const emit = defineEmits(['update:modelValue'])

const props = withDefaults(defineProps<{
  value: string | number
  modelValue?: Array<string | number>
  disabled?: boolean
}>(), {
  disabled: false,
})

const val = useVModel(props, 'modelValue', emit)

provide('selectedValues', val)
provide('value', computed(() => props.value))
provide('disabled', computed(() => props.disabled))

const isSelected = computed(() => val.value?.includes(props.value))
</script>
