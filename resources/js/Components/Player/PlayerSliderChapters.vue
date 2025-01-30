<template>
  <media-slider-chapters class="relative flex h-full w-full items-center rounded-[1px]">
    <template>
      <!-- Chapter -->
      <div class="chapter last-child:mr-0 relative mr-0.5 flex h-full w-full items-center rounded-[1px]">
        <!-- Track -->
        <div class="ring-ring relative z-0 h-[5px] w-full rounded-sm bg-player/30 group-data-[focus]:ring-1">
          <div class="bg-player absolute h-full w-[var(--chapter-fill)] rounded-sm will-change-[width]" />
          <div class="absolute z-10 h-full w-[var(--chapter-progress)] rounded-sm bg-player/50 will-change-[width]" />
        </div>
      </div>
    </template>
  </media-slider-chapters>
</template>

<style scoped>
.chapter {
  contain: layout style;
}
</style>
