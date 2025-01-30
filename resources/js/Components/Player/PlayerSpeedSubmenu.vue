<template>
  <PlayerSubmenu :label="$t('Playback Speed')">
    <template #icon>
      <GaugeIcon class="size-5" />
    </template>
    <template #content>
      <media-speed-radio-group class="w-full flex flex-col" :normal-label="$t('Normal speed')">
        <template>
          <PlayerMenuRadio />
        </template>
      </media-speed-radio-group>
    </template>
  </PlayerSubmenu>
</template>

<script setup lang="ts">
import PlayerMenuRadio from './PlayerMenuRadio.vue'
import PlayerSubmenu from './PlayerSubmenu.vue'
import { GaugeIcon } from 'lucide-vue-next'
</script>
