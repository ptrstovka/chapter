<template>
  <media-menu>
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button as="media-menu-button" variant="ghost" size="icon" class="text-player">
            <slot name="button" />
          </Button>
        </TooltipTrigger>
        <TooltipContent align="center" side="top" :side-offset="32">
          <slot name="tooltip-content" />
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>

    <media-menu-items
      class="z-50 rounded-md shadow-md border bg-popover text-popover-foreground outline-none p-1 flex flex-col overflow-y-auto overscroll-y-contain animate-out fade-out slide-out-to-bottom-2 data-[open]:animate-in data-[open]:fade-in data-[open]:slide-in-from-bottom-4 h-[var(&#45;&#45;menu-height)] max-h-[400px] min-w-[260px] transition-[height] will-change-[height] data-[resizing]:overflow-hidden"
      placement="top end"
    >
      <slot name="content" />
    </media-menu-items>
  </media-menu>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { TooltipProvider, Tooltip, TooltipTrigger, TooltipContent } from '@/Components/Tooltip'
</script>
