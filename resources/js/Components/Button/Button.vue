<template>
  <Primitive data-slot="button" :as="as" :as-child="asChild" :class="cn(buttonVariants({ variant, size }), props.class)">
    <slot v-if="plain" />
    <span v-else class="relative flex w-full">
      <span :class="cn('flex w-full justify-center flex-row items-center gap-1.5 leading-none', { 'invisible': showProcessing }, contentClass || '')">
        <template v-if="showRecentlySuccessful">
          <CheckIcon class="w-5 h-5" />
          {{ recentlySuccessfulLocalizedLabel }}
        </template>
        <slot v-else>
          <component v-if="icon" :is="icon" class="w-4 h-4" />
          <template v-if="label">{{ label }}</template>
        </slot>
      </span>
      <span v-if="showProcessing" class="absolute inset-0 flex items-center justify-center">
        <Spinner class="w-4 h-4" />
      </span>
    </span>
  </Primitive>
</template>

<script setup lang="ts">
import { Primitive } from 'reka-ui'
import { type ButtonProps, buttonVariants } from './'
import { cn } from '@/Utils'
import { computed } from 'vue'
import { CheckIcon } from 'lucide-vue-next'
import { Spinner } from '@/Components/Spinner'

const props = withDefaults(defineProps<ButtonProps>(), {
  as: 'button',
  processing: false,
  plain: false,
})

const recentlySuccessfulLocalizedLabel = computed(() => props.recentlySuccessfulLabel || 'Uložené.')

const showProcessing = computed(() => props.processing)
const showRecentlySuccessful = computed(() => props.recentlySuccessful)
</script>
