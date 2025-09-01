<template>
  <div :class="cn('flex items-center space-x-2', props.class || '')">
    <Switch v-bind="forwarded" :id="id" />
    <Label :for="id">
      <slot />
    </Label>
  </div>
</template>

<script setup lang="ts">
import { Switch } from '.'
import { Label } from '@/Components/Label'
import { useId } from "reka-ui";
import { cn } from '@/Utils'
import {
  type SwitchRootEmits,
  type SwitchRootProps,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const id = useId()

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class'] }>()

const emits = defineEmits<SwitchRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>
