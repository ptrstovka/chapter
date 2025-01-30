<template>
  <PlayerSubmenu :label="$t('Captions')">
    <template #icon>
      <CaptionsIcon class="size-5" />
    </template>
    <template #content>
      <media-captions-radio-group class="w-full flex flex-col">
        <template>
          <PlayerMenuRadio />
        </template>
      </media-captions-radio-group>
    </template>
  </PlayerSubmenu>
</template>

<script setup lang="ts">
import PlayerMenuRadio from './PlayerMenuRadio.vue'
import PlayerSubmenu from './PlayerSubmenu.vue'
import { CaptionsIcon } from 'lucide-vue-next'
</script>
