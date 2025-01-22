<template>
  <Button :as="as" :variant="variant" :size="size" :class="cn(buttonVariants({ variant, size }), $attrs.class ?? '')">
    <span class="relative flex w-full">
      <span
        :class="cn('flex w-full justify-center flex-row items-center gap-1.5 leading-none', { 'invisible': showProcessing }, contentClass || '')">
        <slot v-if="showRecentlySuccessful">
          <CheckIcon class="w-5 h-5" />
          {{ recentlySuccessfulLocalizedLabel }}
        </slot>
        <slot v-else>
          <component v-if="icon" :is="icon" class="w-4 h-4" />
          <span v-if="label">{{ label }}</span>
        </slot>
      </span>
      <span v-if="showProcessing" class="absolute inset-0 flex items-center justify-center">
        <Spinner class="w-4 h-4" />
      </span>
    </span>
  </Button>
</template>

<script setup lang="ts">
import { type ButtonProps, buttonVariants, Button } from './'
import { cn } from '@/Utils'
import { computed } from 'vue'
import { CheckIcon } from 'lucide-vue-next'
import { Spinner } from '@/Components/Spinner'

const props = withDefaults(defineProps<ButtonProps>(), {
  as: 'button',
  processing: false
})

const recentlySuccessfulLocalizedLabel = computed(() => props.recentlySuccessfulLabel || 'Saved.')

const showProcessing = computed(() => props.processing)
const showRecentlySuccessful = computed(() => props.recentlySuccessful === true)
</script>
