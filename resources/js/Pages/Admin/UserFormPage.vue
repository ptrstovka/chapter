<template>
  <AdminLayout :title="$t('Users')">
    <Panel class="flex-1 flex flex-col">
      <PanelHeader>
        <PanelTitle>{{ $t('User :name', { name: user.name }) }}</PanelTitle>
      </PanelHeader>
      <PanelContent class="flex-1">
        <PanelItem :label="$t('ID')">
          <code class="flex h-9 items-center">{{ user.id }}</code>
        </PanelItem>
        <PanelItem :label="$t('Name')">
          <FormControl :error="form.errors.name">
            <Input v-model="form.name" class="sm:max-w-md" />
          </FormControl>
        </PanelItem>
        <PanelItem :label="$t('E-Mail')">
          <FormControl :error="form.errors.email">
            <Input v-model="form.email" class="sm:max-w-md" />
          </FormControl>
        </PanelItem>
        <PanelItem :label="$t('Is Admin')">
          <FormControl :error="form.errors.is_admin">
            <div class="inline-flex h-9 items-center">
              <Switch v-model="form.is_admin" />
            </div>
          </FormControl>
        </PanelItem>
        <PanelItem :label="$t('Role')" class="border-b border-border/50" :center="false">
          <FormControl class="md:max-w-md">
            <RadioGroup v-model="form.role" class="border rounded-md shadow-xs divide-y divide-input border-input gap-0">
              <div class="flex flex-row p-3 gap-3 w-full">
                <RadioGroupItem id="role:student" value="student" />
                <div class="pt-px">
                  <Label for="role:student">{{ $t('Student') }}</Label>
                </div>
              </div>
              <div class="flex flex-row p-3 gap-3">
                <RadioGroupItem id="role:author" value="author" />
                <div class="pt-px w-full flex flex-col gap-3">
                  <Label for="role:author">{{ $t('Author') }}</Label>
                  <FormCombobox
                    v-if="form.role === 'author'"
                    class="w-full"
                    :options="authors"
                    v-model="form.author"
                    :search-label="$t('Search author…')"
                    :not-found-label="$t('No author found')"
                    :placeholder="$t('Select author…')"
                  />
                </div>
              </div>
            </RadioGroup>
          </FormControl>
        </PanelItem>
      </PanelContent>
      <PanelFooter class="flex justify-end gap-4">
        <Button :recently-successful="form.recentlySuccessful" :processing="form.processing" @click="save" :label="$t('Save')" />
      </PanelFooter>
    </Panel>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { FormControl, FormCombobox } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { RadioGroup, RadioGroupItem } from "@/Components/RadioGroup";
import { Switch } from "@/Components/Switch";
import { Label } from "@/Components/Label";
import { AdminLayout } from "@/Layouts";
import { useForm } from "@inertiajs/vue3";
import type { SelectOption } from "@stacktrace/ui";

const props = defineProps<{
  user: {
    id: number
    name: string
    email: string
    isAdmin: boolean
    role: 'student' | 'author'
    author: number | null
  }
  authors: Array<SelectOption<number>>
}>()

const form = useForm(() => ({
  name: props.user.name,
  email: props.user.email,
  is_admin: props.user.isAdmin,
  role: props.user.role,
  author: props.user.author,
}))

const save = () => {
  form.patch(route('admin.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>
