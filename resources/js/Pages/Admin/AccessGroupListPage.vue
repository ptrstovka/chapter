<template>
  <AdminLayout :title="$t('Groups')">
    <DataTable
      :table="groups"
      :empty-table-message="$t('No Groups')"
      :empty-table-description="$t('Get started by creating first group.')"
    >
      <template #empty-table>
        <LinkButton :href="route('admin.groups.create')" class="mt-4">
          <PlusIcon /> {{ $t('Create Group') }}
        </LinkButton>
      </template>

      <template #actions>
        <LinkButton :href="route('admin.groups.create')">
          <PlusIcon /> {{ $t('Create Group') }}
        </LinkButton>
      </template>
    </DataTable>
  </AdminLayout>
</template>

<script setup lang="ts">
import { LinkButton } from "@/Components/Button";
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { AdminLayout } from "@/Layouts";
import { PlusIcon } from "lucide-vue-next";

defineProps<{
  groups: DataTableValue
}>()
</script>
