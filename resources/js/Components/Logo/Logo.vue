<template>
  <img
    v-if="page.props.app.logo"
    :src="page.props.app.logo"
    :alt="page.props.app.name"
    :class="cn('size-8 object-center', $attrs.class || '')"
  >
  <div
    v-else
    :class="cn('size-8 inline-flex items-center justify-center bg-primary text-primary-foreground rounded', $attrs.class || '')"
  >
    <TvMinimalPlayIcon class="size-5" />
  </div>
</template>

<script setup lang="ts">
import { cn } from "@/Utils";
import { usePage } from "@inertiajs/vue3";
import { TvMinimalPlayIcon } from 'lucide-vue-next'

const page = usePage()
</script>
