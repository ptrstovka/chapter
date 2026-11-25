<template>
  <div class="flex items-center gap-2 rounded-md border p-4 text-sm text-muted-foreground" role="status">
    <Spinner v-if="isProcessing" class="size-4 shrink-0" aria-hidden="true" />
    <span v-if="isProcessing">{{ $t('Video is being prepared') }}</span>
    <span v-else-if="status === 'failed'">{{ $t('Video processing failed.') }}</span>
    <span v-else>{{ $t('Video is unavailable.') }}</span>
  </div>
</template>

<script setup lang="ts">
import { Spinner } from '@/Components/Spinner'
import type { VideoSource } from '@/Types'
import { computed } from 'vue'

const props = defineProps<{ status: VideoSource['status'] }>()
const isProcessing = computed(() => props.status === 'queued' || props.status === 'processing')
</script>
