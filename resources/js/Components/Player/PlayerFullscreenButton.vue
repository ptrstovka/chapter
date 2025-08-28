<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger>
        <Button as="media-fullscreen-button" variant="ghost" size="icon" class="text-player aria-hidden:hidden">
          <FullscreenIcon class="group-data-[fullscreen]/media:hidden size-6" />
          <Minimize2Icon class="group-data-[fullscreen]/media:block hidden size-6" />
        </Button>
      </TooltipTrigger>
      <TooltipContent align="end" side="top" :side-offset="32">
        <span class="group-data-[fullscreen]/media:hidden">{{ $t('Enter Fullscreen') }}</span>
        <span class="group-data-[fullscreen]/media:block hidden">{{ $t('Exit Fullscreen') }}</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/Tooltip'
import { FullscreenIcon, Minimize2Icon } from 'lucide-vue-next'
</script>
