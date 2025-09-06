<template>
  <AdminLayout :title="$t('Invitations')">
    <DataTable
      :table="invitations"
      :empty-table-message="$t('No invitations')"
      :empty-table-description="$t('Get started by creating first invitation.')"
    >
      <template #empty-table>
        <Button @click="createDialog.activate" :icon="PlusIcon" :label="$t('Create Invitation')" class="mt-4" />
      </template>

      <template #actions>
        <Button @click="createDialog.activate" :icon="PlusIcon" :label="$t('Create Invitation')" />
      </template>
    </DataTable>

    <Dialog :control="createDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t('New Invitation') }}</DialogTitle>
          <DialogDescription>{{ $t('Use invitations to control who can register in to the Platform.') }}</DialogDescription>
        </DialogHeader>

        <div class="flex flex-col gap-4">
          <FormControl :label="$t('Code')" :help="$t('Leave empty to generate automatically or enter any custom case-insensitive alphanumeric code up to 12 characters.')" :error="form.errors.code">
            <Input v-model="form.code" :placeholder="$t('Generate automatically')" />
          </FormControl>

          <FormControl :label="$t('Expiration')" :error="form.errors.expires_at">
            <DatePicker v-model="form.expires_at" :placeholder="$t('Never')" />
          </FormControl>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="createDialog.deactivate">{{ $t('Cancel') }}</Button>
          <Button @click="create" :processing="form.processing">{{ $t('Create') }}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { DataTable, type DataTableValue } from "@/Components/DataTable";
import { DatePicker } from "@/Components/DatePicker";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { AdminLayout } from "@/Layouts";
import { useForm } from "@inertiajs/vue3";
import { onActivated, useToggle } from "@stacktrace/ui";
import { PlusIcon } from "lucide-vue-next";
import { Dialog, DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription } from '@/Components/Dialog'

defineProps<{
  invitations: DataTableValue
}>()

const createDialog = useToggle()

const form = useForm(() => ({
  code: '',
  expires_at: null,
}))

const create = () => {
  form.post(route('admin.invitations.store'), {
    preserveScroll: true,
    onSuccess: () => {
      createDialog.deactivate()
    }
  })
}

onActivated(createDialog, () => {
  form.reset()
})
</script>
