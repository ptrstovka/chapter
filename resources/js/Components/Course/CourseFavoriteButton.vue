<template>
  <button class="p-3 text-muted-foreground hover:text-red-500 transition-colors" @click.prevent.stop="toggle">
    <HeartIcon class="size-4 transition-colors" :class="{ 'fill-red-500 text-red-500': isFavorite }" />
  </button>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { HeartIcon } from 'lucide-vue-next'

const props = defineProps<{
  slug: string
  isFavorite: boolean
}>()

const form = useForm(() => ({}))

const toggle = () => {
  if (form.processing) {
    return
  }

  const options = {
    showProgress: false,
    preserveScroll: true,
  }

  if (props.isFavorite) {
    form.delete(route('courses.unfavorite', props.slug), options)
  } else {
    form.post(route('courses.favorite', props.slug), options)
  }
}
</script>
