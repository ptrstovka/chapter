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
      <SwitchControl
        v-bind="forwarded"
        :model-value="field.value"
        @update:model-value="field.updateAndSave"
        :class="switchClass"
      >
        <slot />
      </SwitchControl>
    </FormControl>
  </AutoSaveFormField>
</template>

<script setup lang="ts">
import { reactiveOmit } from "@vueuse/core";
import { useForwardProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { AutoSaveFormField } from '.'
import { FormControl } from '@/Components/Form'
import { SwitchControl } from '@/Components/Switch'

interface SwitchProps {
  id?: string | undefined
  disabled?: boolean
}

interface FormControlProps {
  variant?: 'vertical' | 'horizontal'
  label?: string
  help?: string
}

const props = defineProps<SwitchProps & FormControlProps & {
  name: string
  required?: boolean
  class?: HTMLAttributes['class']
  switchClass?: HTMLAttributes['class']
}>()

const delegated = reactiveOmit(props, 'id', 'disabled', 'label', 'required', 'class', 'switchClass', 'variant', 'help')
const forwarded = useForwardProps(delegated)
</script>
