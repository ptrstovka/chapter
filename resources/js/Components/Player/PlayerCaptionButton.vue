<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button as="media-caption-button" variant="ghost" size="icon" class="text-player aria-hidden:hidden">
          <CaptionsOffIcon class="group-data-[captions]/media:block hidden size-6" />
          <CaptionsIcon class="group-data-[captions]/media:hidden size-6" />
        </Button>
      </TooltipTrigger>
      <TooltipContent align="center" side="top" :side-offset="32">
        <span class="group-data-[captions]/media:block hidden">{{ $t('Closed-Captions Off') }}</span>
        <span class="group-data-[captions]/media:hidden">{{ $t('Closed-Captions On') }}</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/Tooltip'
import { CaptionsIcon, CaptionsOffIcon } from 'lucide-vue-next'
</script>
