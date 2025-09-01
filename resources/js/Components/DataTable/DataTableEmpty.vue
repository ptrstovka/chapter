<template>
  <div :class="cn('overflow-hidden flex items-center flex-col', $attrs.class || '')">
    <div class="flex flex-col max-w-sm items-center relative pt-12 pb-12">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 480 480" class="text-foreground/10 h-[520px] -translate-y-2/3 absolute">
        <mask id="c" width="480" height="480" x="0" y="0" maskUnits="userSpaceOnUse" style="mask-type:alpha"><path fill="url(#a)" d="M0 0h480v480H0z"/></mask>
        <g stroke="currentColor" clip-path="url(#b)" mask="url(#c)">
          <g clip-path="url(#d)"><path d="M.5 0v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480m32-480v480"/></g>
          <path d="M.5.5h479v479H.5z"/>
          <g clip-path="url(#e)"><path d="M0 31.5h480M0 63.5h480M0 95.5h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480m-480 32h480"/></g>
          <path d="M.5.5h479v479H.5z"/>
        </g>
        <defs>
          <clipPath id="b"><path fill="#fff" d="M0 0h480v480H0z"/></clipPath>
          <clipPath id="d"><path fill="#fff" d="M0 0h480v480H0z"/></clipPath>
          <clipPath id="e"><path fill="#fff" d="M0 0h480v480H0z"/></clipPath>
          <radialGradient id="a" cx="0" cy="0" r="1" gradientTransform="rotate(90 0 240) scale(240)" gradientUnits="userSpaceOnUse"><stop/><stop offset="1" stop-opacity="0"/></radialGradient>
        </defs>
      </svg>

      <div v-if="icon" class="w-16 h-16 rounded-full bg-muted text-foreground inline-flex items-center justify-center mb-8">
        <component :is="icon" class="w-6 h-6" />
      </div>

      <p class="font-semibold text-center" v-if="title">{{ title }}</p>
      <p class="max-w-lg text-center text-muted-foreground text-sm mt-1" v-if="description">{{ description }}</p>

      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { type Component } from 'vue'
import { cn } from '@/Utils'

defineProps<{
  title?: string
  description?: string
  icon?: Component
}>()
</script>
