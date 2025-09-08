<template>
  <AdminLayout :title="$t('Users')">
    <DataTable :table="users" />
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { AdminLayout } from "@/Layouts";

defineProps<{
  users: DataTableValue
}>()
</script>
