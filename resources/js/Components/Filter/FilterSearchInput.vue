<template>
  <Input :debounce="300" v-model="value" placeholder="Search…" class="h-8 w-[250px]" />
</template>

<script setup lang="ts">
import { useVModel } from "@vueuse/core";
import { Input } from '@/Components/Input'

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue?: string | number
}>()

const value = useVModel(props, 'modelValue', emit)
</script>
