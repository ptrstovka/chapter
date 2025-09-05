<template>
  <AdminLayout :title="$t('Settings')">
    <AutoSaveFormProvider :form="form">
      <Panel>
        <PanelHeader>
          <PanelTitle>{{ $t('General') }}</PanelTitle>
        </PanelHeader>
        <PanelContent>
          <PanelItem :label="$t('Platform Name')" :description="$t('The display name of the Platform.')">
            <AutoSaveFormInputField name="platform_name" class="sm:max-w-sm" placeholder="Chapter" />
          </PanelItem>
          <PanelItem :label="$t('Platform Locale')" :description="$t('The default platform locale.')">
            <AutoSaveFormSelectField name="platform_locale" :options="availableLocales" class="sm:max-w-sm" select-class="w-full" />
          </PanelItem>
        </PanelContent>
      </Panel>
    </AutoSaveFormProvider>
  </AdminLayout>
</template>

<script setup lang="ts">
import { useAutoSaveForm, AutoSaveFormProvider, AutoSaveFormInputField, AutoSaveFormSelectField } from "@/Components/AutoSaveForm";
import { AdminLayout } from "@/Layouts";
import { Panel, PanelHeader, PanelTitle, PanelContent, PanelItem } from '@/Components/Panel'
import type { SelectOption } from "@stacktrace/ui";

const props = defineProps<{
  platformName: string | null
  platformLocale: string
  availableLocales: Array<SelectOption>
}>()

const form = useAutoSaveForm(() => ({
  platform_name: props.platformName || '',
  platform_locale: props.platformLocale,
}), {
  method: 'patch',
  url: route('admin.settings.update')
})
</script>
