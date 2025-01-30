<template>
  <media-menu>
    <PlayerSubmenuButton :label="label" :hint="hint">
      <template #icon>
        <slot name="icon" />
      </template>
    </PlayerSubmenuButton>
    <media-menu-items class="hidden w-full flex-col items-start justify-center outline-none data-[keyboard]:mt-[3px] data-[open]:inline-block">
      <slot name="content" />
    </media-menu-items>
  </media-menu>
</template>

<script setup lang="ts">
import PlayerSubmenuButton from './PlayerSubmenuButton.vue'

defineProps<{
  label: string
  hint?: string | undefined
}>()
</script>
