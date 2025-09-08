<template>
  <AdminLayout :title="$t('Instructors')">
    <DataTable
      :table="instructors"
      :empty-table-message="$t('No Instructors')"
      :empty-table-description="$t('Get started by adding first instructor.')"
    >
      <template #empty-table>
        <LinkButton :href="route('admin.instructors.create')" class="mt-4">
          <PlusIcon /> {{ $t('Create Instructor') }}
        </LinkButton>
      </template>

      <template #actions>
        <LinkButton :href="route('admin.instructors.create')">
          <PlusIcon /> {{ $t('Create Instructor') }}
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
  instructors: DataTableValue
}>()
</script>
