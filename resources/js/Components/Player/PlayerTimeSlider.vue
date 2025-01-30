<template>
  <media-time-slider
    class="group relative mx-[7.5px] inline-flex h-10 w-full cursor-pointer touch-none select-none items-center outline-none"
  >
    <PlayerSliderChapters />
    <PlayerSliderThumb />
    <PlayerSliderPreview :thumbnails="thumbnails">
      <div class="mt-2 text-sm" data-part="chapter-title" />
      <media-slider-value class="text-sm text-player font-medium" />
    </PlayerSliderPreview>
  </media-time-slider>
</template>

<script setup lang="ts">
import PlayerSliderChapters from './PlayerSliderChapters.vue'
import PlayerSliderPreview from './PlayerSliderPreview.vue'
import PlayerSliderThumb from './PlayerSliderThumb.vue'

defineProps<{
  thumbnails?: string
}>()
</script>
