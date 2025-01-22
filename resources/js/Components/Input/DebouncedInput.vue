<template>
  <Input v-model="value" :class="cn(className || '')" :placeholder="placeholder" v-bind="rest" />
</template>
<script setup lang="ts">
import { computed, ref, useAttrs, watch } from 'vue'
import { cn } from '@/Utils'
import debounce from "lodash/debounce";
import { Input } from './'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  debounce?: number | undefined
  maxWait?: number | undefined
  placeholder?: string | undefined
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const { class: className, ...rest } = useAttrs()

const value = ref(props.modelValue || '')

const emitValue = (val: string | number) => emits('update:modelValue', val)
const emitDebouncedValue = debounce(emitValue, props.debounce, { maxWait: props.maxWait })
const shouldDebounce = props.debounce && props.debounce > 0

watch(value, val =>{
  if (props.modelValue != val) {
    if (val) {
      if (shouldDebounce) {
        emitDebouncedValue(val)
      } else {
        emitValue(val)
      }
    } else {
      emitDebouncedValue.cancel()
      emitValue(val)
    }
  }
})

watch(computed(() => props.modelValue), it => {
  if (value.value != it) {
    value.value = it!
  }
})
</script>
