<template>
  <AdminLayout :title="$t('SSO Providers')">
    <DataTable
      :table="providers"
      :empty-table-message="$t('No SSO providers')"
      :empty-table-description="$t('Get started by configuring first SSO provider.')"
    >
      <template #empty-table>
        <LinkButton :href="route('admin.sso.create')" class="mt-4">
          <PlusIcon /> {{ $t('Configure Provider') }}
        </LinkButton>
      </template>

      <template #actions>
        <LinkButton :href="route('admin.sso.create')">
          <PlusIcon /> {{ $t('Configure Provider') }}
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
  providers: DataTableValue
}>()
</script>
