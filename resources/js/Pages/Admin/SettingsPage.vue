<template>
  <AdminLayout :title="$t('Settings')">
    <AutoSaveFormProvider :form="form">
      <div class="flex flex-col divide-y">
        <Panel>
          <PanelHeader class="bg-accent/40">
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

        <Panel>
          <PanelHeader class="bg-accent/40">
            <PanelTitle>{{ $t('Access') }}</PanelTitle>
          </PanelHeader>
          <PanelContent>
            <PanelItem :label="$t('Registration')" :description="$t('Enable users to create an account.')" center :wrap="false">
              <AutoSaveFormSwitchField name="enable_registration" />
            </PanelItem>
            <PanelItem v-if="form.value.enable_registration" :label="$t('Invitations')" :description="$t('Require an invitation code during registration.')" center :wrap="false">
              <AutoSaveFormSwitchField name="enable_invitations" />
            </PanelItem>
            <PanelItem :label="$t('Single Sign-On')" :description="$t('Allow users to login through an SSO provider.')" center :wrap="false">
              <AutoSaveFormSwitchField name="enable_single_sign_on" />
            </PanelItem>
            <PanelItem :label="$t('Password Login')" :description="$t('Enable users to login with email and password.')" center :wrap="false">
              <AutoSaveFormSwitchField name="enable_password_login" />
            </PanelItem>
          </PanelContent>
        </Panel>
      </div>
    </AutoSaveFormProvider>
  </AdminLayout>
</template>

<script setup lang="ts">
import {
  useAutoSaveForm, AutoSaveFormProvider, AutoSaveFormInputField, AutoSaveFormSelectField,
  AutoSaveFormSwitchField,
} from "@/Components/AutoSaveForm";
import { AdminLayout } from "@/Layouts";
import { Panel, PanelHeader, PanelTitle, PanelContent, PanelItem } from '@/Components/Panel'
import type { SelectOption } from "@stacktrace/ui";

const props = defineProps<{
  platformName: string | null
  platformLocale: string
  availableLocales: Array<SelectOption>
  enableRegistration: boolean
  enableInvitations: boolean
  enableSingleSignOn: boolean
  enablePasswordLogin: boolean
}>()

const form = useAutoSaveForm(() => ({
  platform_name: props.platformName || '',
  platform_locale: props.platformLocale,
  enable_registration: props.enableRegistration,
  enable_invitations: props.enableInvitations,
  enable_single_sign_on: props.enableSingleSignOn,
  enable_password_login: props.enablePasswordLogin,
}), {
  method: 'patch',
  url: route('admin.settings.update')
})
</script>
