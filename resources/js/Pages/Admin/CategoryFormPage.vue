<template>
  <AdminLayout :title="category ? $t('Category :value', { value: category.title }) : $t('New Category')">
    <Panel class="flex-1 flex flex-col">
      <PanelHeader>
        <PanelTitle>{{ category ? $t('Category :value', { value: category.title }) : $t('New Category') }}</PanelTitle>
      </PanelHeader>
      <PanelContent class="flex-1">
        <PanelItem v-if="category" :label="$t('URL Slug')">
          <code class="flex h-9 items-center">{{ category.slug }}</code>
        </PanelItem>
        <PanelItem :label="$t('Title')" class="border-b border-border/50">
          <FormControl :error="form.errors.title">
            <Input v-model="form.title" class="sm:max-w-md" />
          </FormControl>
        </PanelItem>
      </PanelContent>
      <PanelFooter class="flex justify-end gap-4">
        <Button v-if="category && category.canDelete" @click="destroy" variant="ghost-desctructive" :label="$t('Delete')" />

        <Button :recently-successful="form.recentlySuccessful" :processing="form.processing" @click="save" :label="$t(category ? 'Save' : 'Create')" />
      </PanelFooter>
    </Panel>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { useSaveShortcut } from "@/Composables/useKeyboard.ts";
import { AdminLayout } from "@/Layouts";
import { useForm } from "@inertiajs/vue3";
import { asyncRouter } from "@stacktrace/ui";
import { trans } from "laravel-vue-i18n";

const props = defineProps<{
  category?: {
    id: number
    title: string
    slug: string
    canDelete: boolean
  }
}>()

const form = useForm(() => ({
  title: props.category?.title || '',
}))

const save = () => {
  const method = props.category ? 'patch' : 'post'
  const url = props.category ? route('admin.categories.update', props.category.id) : route('admin.categories.store')

  form.submit(method, url, {
    onSuccess: () => {
      form.reset()
    }
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(
  trans('Are you sure you want to delete this category?'),
  async () => {
    const id = props.category?.id
    if (id) {
      await asyncRouter.delete(route('admin.categories.destroy', id))
    }
  },
  { title: trans('Delete Category'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true },
)

useSaveShortcut(() => save())
</script>
