<template>
  <div :class="cn('flex items-center space-x-2', $attrs.class || '')">
    <Checkbox v-model="checked" :value="value" :indeterminate="indeterminate" :id="id" />
    <Label :for="id">
      <slot />
    </Label>
  </div>
</template>

<script setup lang="ts">
import { useVModel } from "@vueuse/core";
import { cn } from "@/Utils";
import { Label } from "@/Components/Label";
import { Checkbox } from ".";
import { useId } from "reka-ui";

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue?: any
  value?: any
  indeterminate?: boolean
}>()

const checked = useVModel(props, 'modelValue', emit)

const id = useId()
</script>
