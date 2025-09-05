<template>
  <AutoSaveFormField :name="name" v-slot="{ field, error }">
    <FormControl
      :for="id"
      :label="label"
      :error="error"
      :required="required"
      :class="props.class"
      :variant="variant"
      :help="help"
    >
      <Input
        v-bind="forwarded"
        :model-value="field.value"
        @update:model-value="field.update"
        @blur="field.save()"
        :class="inputClass"
      />

      <slot />
    </FormControl>
  </AutoSaveFormField>
</template>

<script setup lang="ts">
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { AutoSaveFormField } from '.'
import { FormControl } from '@/Components/Form'
import { Input } from '@/Components/Input'

interface InputProps {
  id?: string | undefined
  disabled?: boolean
  type?: string | undefined
  placeholder?: string | undefined
}

interface FormControlProps {
  variant?: 'vertical' | 'horizontal'
  label?: string
  help?: string
}

const props = defineProps<InputProps & FormControlProps & {
  name: string
  required?: boolean
  class?: HTMLAttributes['class']
  inputClass?: HTMLAttributes['class']
}>()

const delegated = reactiveOmit(props, 'id', 'disabled', 'type', 'label', 'required', 'class', 'inputClass', 'variant', 'help')
const forwarded = useForwardProps(delegated)
</script>
