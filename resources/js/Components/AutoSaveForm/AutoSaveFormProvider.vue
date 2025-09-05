<template>
  <slot />
</template>

<script setup lang="ts" generic="TFormData extends FormData">
import type { FormData, AutoSaveForm } from '.'
import { provideAutoSaveForm } from '.'

const props = defineProps<{
  form: AutoSaveForm<TFormData>
}>()

provideAutoSaveForm({
  form: props.form
})
</script>
