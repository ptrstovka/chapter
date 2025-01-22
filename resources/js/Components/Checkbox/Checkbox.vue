<template>
  <input
    ref="inputEl"
    type="checkbox"
    v-model="checked"
    :value="value"
    :class="cn('w-4 h-4 text-primary bg-background indeterminate:bg-primary dark:indeterminate:bg-background checked:bg-primary dark:checked:bg-background border rounded border-muted-foreground/40 appearance-none p-0 inline-flex align-middle flex-shrink-0 cursor-pointer focus-visible:outline-none focus-visible:ring-ring focus-visible:ring-2 focus-visible:ring-offset-1 disabled:bg-muted disabled:opacity-50 disabled:cursor-auto')"
  >
</template>

<script setup lang="ts">
import { useVModel } from "@vueuse/core";
import { cn } from "@/Utils";
import { onMounted, ref, watch } from "vue";

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue?: any
  value?: any
  indeterminate?: boolean
}>()

const checked = useVModel(props, 'modelValue', emit)

const inputEl = ref<HTMLInputElement>()

const setIndeterminate = (value: boolean) => {
  if (inputEl.value) {
    inputEl.value.indeterminate = value
  }
}

onMounted(() => {
  setIndeterminate(props.indeterminate)
})

watch(() => props.indeterminate, () => {
  setIndeterminate(props.indeterminate)
})
</script>
<style scoped>
input[type="checkbox"] {
  print-color-adjust: exact;
  user-select: none;
}

input[type="checkbox"]:checked {
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E");
}

input[type="checkbox"]:indeterminate {
  background-size: 100% 100%;
  background-position: center;
  background-repeat: no-repeat;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 16 16'%3E%3Cpath stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 8h8'/%3E%3C/svg%3E");
}
</style>
