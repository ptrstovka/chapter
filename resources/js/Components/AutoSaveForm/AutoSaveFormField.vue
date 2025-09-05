<template>
  <slot
    :field="{ value: form.value[props.name], update: onChange, save: onSave, updateAndSave: changeAndSave }"
    :error="form.errors[props.name] || undefined"
  />
</template>

<script setup lang="ts">
import { injectAutoSaveForm } from '.'

const props = defineProps<{
  name: string
}>()

const { form } = injectAutoSaveForm()

const onChange = (value: any) => {
  form.value[props.name] = value
}

const changeAndSave = (value: any) => {
  onChange(value)
  onSave()
}

const onSave = () => {
  form.submit()
}
</script>
