<template>
  <div v-if="variant === 'horizontal'" class="form-control w-full flex flex-col sm:flex-row" :class="{ 'has-error': !!error }">
    <div class="flex flex-col space-y-2 sm:w-2/5 pb-2 sm:pb-0 sm:pr-4" :class="{ 'sm:pt-2.5': !help }">
      <FormLabel v-if="label" :for="props.for || undefined" :error="props.error || ''">{{ label }} <span v-if="required" class="text-destructive">*</span></FormLabel>
      <FormDescription class="hidden sm:block" v-if="help">{{ help }}</FormDescription>
    </div>
    <div class="flex flex-col sm:w-3/5">
      <slot />
      <FormMessage v-if="error" :message="error" class="mt-2" />
      <FormDescription class="sm:hidden mt-2" v-if="help">{{ help }}</FormDescription>
    </div>
  </div>

  <FormItem class="form-control" v-else :class="{ 'has-error': !!error }">
    <FormLabel v-if="label" :for="props.for || undefined" :error="props.error || ''">{{ label }} <span v-if="required" class="text-destructive">*</span></FormLabel>
    <div class="w-full">
      <slot />
    </div>
    <FormDescription v-if="help">{{ help }}</FormDescription>
    <FormMessage v-if="error" :message="error" />
  </FormItem>
</template>

<script setup lang="ts">
import { FormItem, FormLabel, FormMessage, FormDescription } from './'

const props = withDefaults(defineProps<{
  variant?: 'vertical' | 'horizontal'
  for?: string
  label?: string | null | undefined
  help?: string | null | undefined
  error?: string | null | undefined
  required?: boolean
}>(), {
  variant: 'vertical',
})
</script>
