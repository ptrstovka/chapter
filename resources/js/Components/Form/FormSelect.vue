<template>
  <Select v-bind="forwarded" v-model="value">
    <SelectTrigger :class="props.class">
      <SelectValue :placeholder="placeholder" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem v-for="option in options" :value="option.value">{{ option.label }}</SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
import { type SelectOption } from '@stacktrace/ui'
import type { SelectRootEmits, SelectRootProps } from 'reka-ui'
import { useForwardProps } from 'reka-ui'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/Components/Select'
import { computed, type HTMLAttributes } from "vue";
import { useVModel } from "@vueuse/core";

const props = defineProps<SelectRootProps & {
  options: Array<SelectOption>
  placeholder?: string | undefined
  class?: HTMLAttributes['class']
  modelValue?: string | number | undefined
}>()
const emits = defineEmits<SelectRootEmits>()

const delegatedProps = computed(() => {
  const { options, placeholder, class: _, modelValue, ...delegated } = props

  return delegated
})

const value = useVModel(props, 'modelValue', emits)

const forwarded = useForwardProps(delegatedProps)
</script>
