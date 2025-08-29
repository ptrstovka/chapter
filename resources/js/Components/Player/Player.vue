<template>
  <media-player
      :class="cn('group/media relative w-full aspect-video bg-secondary overflow-hidden rounded-xl ring-ring data-[focus]:ring-1', $attrs.class || '')"
      :src="src"
      crossOrigin
      playsInline
      @ended="onPlaybackEnded"
      @rate-change="onPlaybackRateChanged"
      :autoplay="autoplay"
      :playback-rate="playbackRate"
      ref="$player"
  >
    <media-provider>
      <media-poster
        v-if="poster"
        class="absolute inset-0 block h-full w-full rounded-md opacity-0 transition-opacity data-[visible]:opacity-100 [&>img]:h-full [&>img]:w-full [&>img]:object-cover"
        :src="poster"
      />
    </media-provider>

    <PlayerGestures />
    <PlayerCaptions v-if="captions" />
    <media-controls class="group-data-[controls]/media:opacity-100 absolute inset-0 z-10 flex h-full w-full flex-col bg-gradient-to-t from-black/10 to-transparent opacity-0 transition-opacity">
      <div class="flex-1" />
      <media-controls-group class="flex w-full items-center px-2">
        <PlayerTimeSlider :thumbnails="playbackThumbnails" />
      </media-controls-group>
      <media-controls-group class="-mt-0.5 flex w-full items-center px-2 pb-2">
        <PlayerPlayButton />
        <PlayerMuteButton />
        <PlayerVolumeSlider />
        <PlayerTimeGroup />
        <PlayerChapterTitle />
        <div class="flex-1" />
        <PlayerCaptionButton v-if="captions" />
        <PlayerMenu v-if="showSettings">
          <template #button>
            <SettingsIcon class="size-6" />
          </template>
          <template #content>
            <PlayerCaptionSubmenu v-if="captions" />
            <PlayerSpeedSubmenu v-if="speed" />
            <slot name="settings" />
          </template>
          <template #tooltip-content>
            <span>{{ $t('Settings') }}</span>
          </template>
        </PlayerMenu>
        <PlayerPIPButton />
        <PlayerFullscreenButton />
      </media-controls-group>
    </media-controls>
  </media-player>
</template>

<script setup lang="ts">
import 'vidstack/player/styles/base.css';
import 'vidstack/player';
import 'vidstack/player/ui';
import { cn } from '@/Utils'
import { useLocalStorage } from '@vueuse/core'
import { SettingsIcon } from 'lucide-vue-next'
import { computed, ref, useSlots } from 'vue'
import type { MediaPlayerElement } from 'vidstack/elements';
import PlayerGestures from './PlayerGestures.vue'
import PlayerCaptions from './PlayerCaptions.vue'
import PlayerTimeSlider from './PlayerTimeSlider.vue'
import PlayerVolumeSlider from './PlayerVolumeSlider.vue'
import PlayerCaptionButton from './PlayerCaptionButton.vue'
import PlayerFullscreenButton from './PlayerFullscreenButton.vue'
import PlayerMuteButton from './PlayerMuteButton.vue'
import PlayerPIPButton from './PlayerPIPButton.vue'
import PlayerPlayButton from './PlayerPlayButton.vue'
import PlayerTimeGroup from './PlayerTimeGroup.vue'
import PlayerChapterTitle from './PlayerChapterTitle.vue'
import PlayerCaptionSubmenu from './PlayerCaptionSubmenu.vue'
import PlayerMenu from './PlayerMenu.vue'
import PlayerSpeedSubmenu from './PlayerSpeedSubmenu.vue'

const emit = defineEmits(['ended'])

const props = withDefaults(defineProps<{
  src: string
  poster?: string | null
  autoplay?: boolean
  captions?: boolean
  speed?: boolean
  playbackThumbnails?: string
}>(), {
  captions: false,
  speed: true,
})

const $player = ref<MediaPlayerElement>()

const onPlaybackEnded = () => {
  emit('ended')
}

const playbackRate = useLocalStorage('PlayerPlaybackRate', 1)
const onPlaybackRateChanged = () => {
  const player = $player.value
  if (player) {
    playbackRate.value = player.playbackRate
  }
}

const slots = useSlots()
const showSettings = computed(() => props.captions === true || props.speed === true || !!slots.settings)
</script>

<style scoped>
media-controls {
  /* These CSS variables are supported out of the box to easily apply offsets to all tooltips/menus.  */
  --media-tooltip-y-offset: 30px;
  --media-menu-y-offset: 30px;
}

media-controls :deep(media-volume-slider) {
  --media-slider-preview-offset: 30px;
}
</style>
