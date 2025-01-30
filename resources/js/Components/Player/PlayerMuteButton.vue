<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger>
        <Button as="media-mute-button" variant="ghost" size="icon" class="group text-player">
          <VolumeXIcon class="hidden size-6 group-data-[state='muted']:block" />
          <Volume1Icon class="hidden size-6 group-data-[state='low']:block" />
          <Volume2Icon class="hidden size-6 group-data-[state='high']:block" />
        </Button>
      </TooltipTrigger>
      <TooltipContent align="center" side="top" :side-offset="32">
        <span class="media-muted:hidden">{{ $t('Mute') }}</span>
        <span class="media-muted:block hidden">{{ $t('Unmute') }}</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/Tooltip'
import { VolumeXIcon, Volume1Icon, Volume2Icon } from 'lucide-vue-next'
</script>
