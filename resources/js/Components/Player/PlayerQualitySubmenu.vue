<template>
  <PlayerSubmenu :label="$t('Quality')">
    <template #icon><Settings2Icon class="size-5" /></template>
    <template #content>
      <media-quality-radio-group class="flex w-full flex-col" :auto-label="$t('Auto')" hide-bitrate>
        <template><PlayerMenuRadio /></template>
      </media-quality-radio-group>
    </template>
  </PlayerSubmenu>
</template>

<script setup lang="ts">
import { Settings2Icon } from 'lucide-vue-next'
import PlayerSubmenu from './PlayerSubmenu.vue'
import PlayerMenuRadio from './PlayerMenuRadio.vue'
</script>
