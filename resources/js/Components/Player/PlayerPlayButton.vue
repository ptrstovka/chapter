<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button as="media-play-button" variant="ghost" size="icon" class="text-player">
          <PlayIcon class="group-data-[paused]/media:block hidden size-6" />
          <PauseIcon class="group-data-[paused]/media:hidden size-6" />
        </Button>
      </TooltipTrigger>
      <TooltipContent align="start" side="top" :side-offset="32">
        <span class="group-data-[paused]/media:block hidden">{{ $t('Play') }}</span>
        <span class="group-data-[paused]/media:hidden">{{ $t('Pause') }}</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { PlayIcon, PauseIcon } from 'lucide-vue-next'
import { Button } from '@/Components/Button'
import { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider } from '@/Components/Tooltip'
</script>
