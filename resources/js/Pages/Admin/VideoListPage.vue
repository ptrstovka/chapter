<template>
  <AdminLayout :title="$t('Videos')">
    <DataTable :table="videos" />
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { AdminLayout } from "@/Layouts";

defineProps<{
  videos: DataTableValue
}>()
</script>
