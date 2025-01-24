<template>
  <media-player ref="player" :src="src" playsInline>
    <media-poster class="vds-poster" v-if="poster" :src="poster"></media-poster>
    <media-provider></media-provider>
    <media-audio-layout></media-audio-layout>
    <media-video-layout></media-video-layout>
  </media-player>
</template>

<script setup lang="ts">
import 'vidstack/bundle';
import type { MediaPlayerElement } from 'vidstack/elements';
import { ref } from 'vue'

const player = ref<MediaPlayerElement>()

defineProps<{
  src: string
  poster: string | null
}>()
</script>
