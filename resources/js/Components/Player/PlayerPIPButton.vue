<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button as="media-pip-button" variant="ghost" size="icon" class="group text-player aria-hidden:hidden">
          <PictureInPictureIcon class="size-6" />
        </Button>
      </TooltipTrigger>
      <TooltipContent align="center" side="top" :side-offset="32">
        <span class="media-pip:hidden">Enter PIP</span>
        <span class="media-pip:block hidden">Exit PIP</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/Tooltip'
import { PictureInPictureIcon } from 'lucide-vue-next'
</script>
