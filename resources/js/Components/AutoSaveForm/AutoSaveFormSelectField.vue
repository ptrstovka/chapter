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
      <FormSelect
        v-bind="forwarded"
        :model-value="field.value"
        @update:model-value="field.updateAndSave"
        :class="selectClass"
        :options="options"
      />

      <slot />
    </FormControl>
  </AutoSaveFormField>
</template>

<script setup lang="ts">
import type { SelectOption } from "@stacktrace/ui";
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { AutoSaveFormField } from '.'
import { FormControl, FormSelect } from '@/Components/Form'

interface SelectProps {
  id?: string | undefined
  disabled?: boolean
  options: Array<SelectOption>
}

interface FormControlProps {
  variant?: 'vertical' | 'horizontal'
  label?: string
  help?: string
}

const props = defineProps<SelectProps & FormControlProps & {
  name: string
  required?: boolean
  class?: HTMLAttributes['class']
  selectClass?: HTMLAttributes['class']
}>()

const delegated = reactiveOmit(props, 'id', 'disabled', 'options', 'label', 'required', 'class', 'selectClass', 'variant', 'help')
const forwarded = useForwardProps(delegated)
</script>
