<template>
  <AdminLayout :title="$t('Categories')">
    <DataTable
      :table="categories"
      :empty-table-message="$t('No Categories')"
      :empty-table-description="$t('Get started by creating first category.')"
    >
      <template #empty-table>
        <LinkButton :href="route('admin.categories.create')" class="mt-4">
          <PlusIcon /> {{ $t('Create Category') }}
        </LinkButton>
      </template>

      <template #actions>
        <LinkButton :href="route('admin.categories.create')">
          <PlusIcon /> {{ $t('Create Category') }}
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
  categories: DataTableValue
}>()
</script>
