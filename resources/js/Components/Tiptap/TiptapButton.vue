<template>
  <Tooltip ignore-non-keyboard-focus :disabled="!tooltip">
    <TooltipTrigger as-child>
      <Button
        v-bind="forwarded"
        plain
        :data-active="active ? 'on' : 'off'"
        variant="ghost"
        :class="cn(
          'h-full has-[>svg]:px-1.5 p-1.5',
          'data-[active=on]:bg-primary data-[active=on]:text-primary-foreground data-[active=on]:hover:bg-primary/90 [&_svg:not([class*=\'text-\'])]:text-primary-foreground',
          'dark:data-[active=on]:hover:bg-primary/90',
          $attrs.class || '',
        )"
      >
        <slot />
      </Button>
    </TooltipTrigger>
    <TooltipContent>
      {{ tooltip }}
    </TooltipContent>
  </Tooltip>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { TooltipTrigger, TooltipContent, Tooltip } from "@/Components/Tooltip";
import { cn } from "@/Utils";
import { reactiveOmit } from "@vueuse/core";
import { useForwardPropsEmits } from "reka-ui";
import type { TiptapButtonProps } from '.'

const emit = defineEmits(['click'])

const props = defineProps<TiptapButtonProps>()

const delegatedProps = reactiveOmit(props, 'active', 'tooltip', 'shortcut')
const forwarded = useForwardPropsEmits(delegatedProps, emit)
</script>
