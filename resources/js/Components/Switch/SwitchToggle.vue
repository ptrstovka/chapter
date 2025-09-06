<template>
  <Switch v-model="form[field]" @update:model-value="save" />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Switch, type SwitchToggleProps } from ".";

const props = withDefaults(defineProps<SwitchToggleProps>(), {
  method: 'post'
})

const form = useForm(() => ({
  [props.field]: props.value,
}))

const save = () => {
  const url = props.url
  if (url) {
    form.submit(props.method, url, {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      }
    })
  }
}
</script>
