<template>
  <AdminLayout :title="instructor ? $t('Instructor :name', { name: instructor.name }) : $t('Create Instructor')">
    <Panel class="flex-1 flex flex-col">
      <PanelHeader v-if="instructor">
        <PanelTitle>{{ $t('Instructor :name', { name: instructor.name }) }}</PanelTitle>
      </PanelHeader>
      <PanelContent class="flex-1">
        <PanelItem v-if="instructor" :label="$t('ID')">
          <code class="flex h-9 items-center">{{ instructor.id }}</code>
        </PanelItem>
        <PanelItem :label="$t('Name')">
          <FormControl :error="form.errors.name">
            <Input v-model="form.name" class="sm:max-w-md" />
          </FormControl>
        </PanelItem>
        <PanelItem :label="$t('Bio')" :center="false">
          <FormControl :error="form.errors.bio">
            <Textarea v-model="form.bio" class="sm:max-w-md" />
          </FormControl>
        </PanelItem>
        <PanelItem :label="$t('Avatar')" :center="false">
          <FormControl :error="form.errors.avatar || form.errors.remove_avatar" class="sm:max-w-md">
            <TemporaryFileInput
              :source="instructor?.avatarUrl || null"
              scope="InstructorAvatar"
              v-model:file="form.avatar"
              v-model:remove="form.remove_avatar"
              v-slot="{ preview }"
              class="group-data-[preview]/temporary-file-input:w-fit group-data-[preview]/temporary-file-input:border-none"
              :drag-label="$t('Drag & drop an image')"
              :pick-label="$t('select an image')"
            >
              <img class="size-28 object-center object-cover" :src="preview">
            </TemporaryFileInput>
          </FormControl>
        </PanelItem>
      </PanelContent>
      <PanelFooter class="flex justify-end gap-4">
        <Button v-if="instructor && instructor.canDelete" @click="destroy" variant="ghost-desctructive" :label="$t('Delete')" />

        <Button :recently-successful="form.recentlySuccessful" :processing="form.processing" @click="save" :label="$t(instructor ? 'Save' : 'Create')" />
      </PanelFooter>
    </Panel>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Textarea } from "@/Components/Textarea";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { useSaveShortcut } from "@/Composables/useKeyboard.ts";
import { AdminLayout } from "@/Layouts";
import { useForm } from "@inertiajs/vue3";
import { TemporaryFileInput } from '@/Components/TemporaryFileInput'
import { asyncRouter } from "@stacktrace/ui";
import { trans } from "laravel-vue-i18n";

const props = defineProps<{
  instructor?: {
    id: number
    name: string
    bio: string | null
    avatarUrl: string | null
    canDelete: boolean
  }
}>()

const form = useForm(() => ({
  name: props.instructor?.name || '',
  bio: props.instructor?.bio || '',
  avatar: null,
  remove_avatar: false,
}))

const save = () => {
  const method = props.instructor ? 'patch' : 'post'
  const url = props.instructor ? route('admin.instructors.update', props.instructor.id) : route('admin.instructors.store')

  form.submit(method, url, {
    onSuccess: () => {
      form.reset()
    }
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(
  trans('Are you sure you want to delete this instructor?'),
  async () => {
    const id = props.instructor?.id
    if (id) {
      await asyncRouter.delete(route('admin.instructors.destroy', id))
    }
  },
  { title: trans('Delete Instructor'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true },
)

useSaveShortcut(() => save())
</script>
