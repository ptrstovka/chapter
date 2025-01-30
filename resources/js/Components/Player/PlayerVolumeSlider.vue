<template>
  <media-volume-slider
    class="group relative mx-[7.5px] inline-flex h-10 w-full max-w-[80px] cursor-pointer touch-none select-none items-center outline-none aria-hidden:hidden"
  >
    <!-- Track -->
    <div class="ring-ring relative z-0 h-[5px] w-full rounded-sm bg-player/30 group-data-[focus]:ring-[3px]">
      <div class="bg-player absolute h-full w-[var(--slider-fill)] rounded-sm will-change-[width]" />
    </div>
    <PlayerSliderThumb />
    <PlayerSliderPreview no-clamp>
      <media-slider-value class="rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground" />
    </PlayerSliderPreview>
  </media-volume-slider>
</template>

<script setup lang="ts">
import PlayerSliderPreview from './PlayerSliderPreview.vue'
import PlayerSliderThumb from './PlayerSliderThumb.vue'
</script>
