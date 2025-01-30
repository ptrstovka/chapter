<template>
  <media-radio-group :value="modelValue" @change="onSelected">
    <slot/>
  </media-radio-group>
</template>

<script setup lang="ts">
const emit = defineEmits(['update:modelValue'])

defineProps<{
  modelValue?: string | undefined
}>()

const onSelected = (e: any) => {
  emit('update:modelValue', e.target.value)
}
</script>
