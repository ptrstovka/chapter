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
      <TemporaryFileInput
        :disabled="disabled"
        :scope="scope"
        :source="field.value"
        :drag-label="$t('Drag & drop an image')"
        :pick-label="$t('select an image')"
        v-model:remove="remove"
        v-model:file="file"
        @update:remove="(r: boolean) => {
          if (r) {
            field.updateAndSave(null)
          }
        }"
        @update:file="(f: string | null) => {
          if (f) {
            field.updateAndSave(f)
          }
        }"
        v-slot="{ preview }"
        class="group-data-[preview]/temporary-file-input:w-fit"
      >
        <div class="h-24 w-32 flex items-center justify-center">
          <img class="h-16 object-center" :src="preview" alt="">
        </div>
      </TemporaryFileInput>
    </FormControl>
  </AutoSaveFormField>
</template>

<script setup lang="ts">
import { FormControl } from "@/Components/Form";
import { type HTMLAttributes, ref } from "vue";
import { AutoSaveFormField } from ".";
import { TemporaryFileInput } from '@/Components/TemporaryFileInput'

interface FormControlProps {
  variant?: 'vertical' | 'horizontal'
  label?: string
  help?: string
}

const props = defineProps<FormControlProps & {
  id?: string | undefined
  disabled?: boolean
  name: string
  scope: string
  required?: boolean
  class?: HTMLAttributes['class']
}>()

const remove = ref(false)
const file = ref<string | null>(null)
</script>
