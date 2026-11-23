<template>
  <AdminLayout :title="$t('Videos')">
    <dl class="grid grid-cols-2 border-b px-4 lg:grid-cols-3" :aria-label="$t('All platform videos')">
      <div v-for="metric in metrics" :key="metric.title" class="min-w-0 space-y-1 border-border py-6 pr-6 even:border-l even:pl-6 lg:border-l lg:pl-6 lg:first:border-l-0 lg:first:pl-0">
        <dt class="text-sm text-muted-foreground">{{ metric.title }}</dt>
        <dd class="text-2xl font-semibold leading-tight tabular-nums">
          {{ metric.value }}
        </dd>
      </div>
    </dl>
    <DataTable :table="videos" />
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { AdminLayout } from "@/Layouts";
import { computed } from "vue";
import { trans } from "laravel-vue-i18n";

const props = defineProps<{
  videos: DataTableValue
  statistics: {
    total: number
    durationSeconds: number
    unknownDurationCount: number
    hlsCount: number
    originalCount: number
    readyCount: number
    queuedCount: number
    processingCount: number
    failedCount: number
    originalBytes: number
    hlsBytes: number
    knownSizeBytes: number
    unknownOriginalSizeCount: number
    unknownHlsSizeCount: number
    labels: {
      total: string
      duration: string
      hlsCount: string
      hlsPercentage: string
      size: string
      originals: string
      hls: string
    }
  }
}>()

const metrics = computed(() => {
  const stats = props.statistics

  return [
    {
      title: trans('Total videos'),
      value: stats.labels.total,
    },
    {
      title: trans('Total duration'),
      value: stats.total > 0 && stats.unknownDurationCount === stats.total ? trans('Unknown') : stats.labels.duration,
    },
    {
      title: trans('Size'),
      value: stats.labels.size,
    },
  ]
})
</script>
