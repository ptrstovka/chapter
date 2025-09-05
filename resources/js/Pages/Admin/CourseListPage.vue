<template>
  <AdminLayout :title="$t('Courses')">
    <DataTable :table="courses" />
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { AdminLayout } from "@/Layouts";

defineProps<{
  courses: DataTableValue
}>()
</script>
