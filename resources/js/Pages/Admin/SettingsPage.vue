<template>
  <AdminLayout :title="$t('Settings')">
    <AutoSaveFormProvider :form="form">
      <div class="flex flex-col divide-y">
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
            <PanelItem :label="$t('Primary Color')" :description="$t('Adjust primary color to your brand. hsl() and oklch() formats are currently supported.')">
              <div class="grid sm:grid-cols-2 sm:max-w-md">
                <AutoSaveFormInputField
                  input-class="rounded-b-none sm:rounded-bl-md sm:rounded-r-none"
                  name="primary_color_background"
                  :placeholder="$t('Background Color')"
                />

                <AutoSaveFormInputField
                  input-class="rounded-t-none sm:rounded-tr-md sm:rounded-l-none -my-px sm:-my-0 sm:-mx-px"
                  name="primary_color_foreground"
                  :placeholder="$t('Text Color')"
                />
              </div>
            </PanelItem>
            <PanelItem :label="$t('Primary Color - Dark')" :description="$t('The primary color used in the dark mode.')">
              <div class="grid sm:grid-cols-2 sm:max-w-md">
                <AutoSaveFormInputField
                  input-class="rounded-b-none sm:rounded-bl-md sm:rounded-r-none"
                  name="primary_color_dark_background"
                  :placeholder="$t('Background Color')"
                />

                <AutoSaveFormInputField
                  input-class="rounded-t-none sm:rounded-tr-md sm:rounded-l-none -my-px sm:-my-0 sm:-mx-px"
                  name="primary_color_dark_foreground"
                  :placeholder="$t('Text Color')"
                />
              </div>
            </PanelItem>
            <PanelItem :label="$t('Logo')" :description="$t('Upload your brand logo.')" :center="false">
              <AutoSaveFormImageField name="logo" scope="AppLogo" class="sm:max-w-xs" />
            </PanelItem>
          </PanelContent>
        </Panel>

        <Panel>
          <PanelHeader>
            <PanelTitle>{{ $t('Features') }}</PanelTitle>
          </PanelHeader>
          <PanelItem :label="$t('Explore Page')" :description="$t('Enable course recommendations through Explore page.')" :wrap="false">
            <AutoSaveFormSwitchField name="enable_explore_page" />
          </PanelItem>
        </Panel>

        <Panel>
          <PanelHeader>
            <PanelTitle>{{ $t('Access') }}</PanelTitle>
          </PanelHeader>
          <PanelContent>
            <PanelItem :label="$t('Registration')" :description="$t('Enable users to create an account.')" :wrap="false">
              <AutoSaveFormSwitchField name="enable_registration" />
            </PanelItem>
            <PanelItem v-if="form.value.enable_registration" :label="$t('Invitations')" :description="$t('Require an invitation code during registration.')" :wrap="false">
              <AutoSaveFormSwitchField name="enable_invitations" />
            </PanelItem>
            <PanelItem :label="$t('Single Sign-On')" :description="$t('Allow users to login through an SSO provider.')" :wrap="false">
              <AutoSaveFormSwitchField name="enable_single_sign_on" />
            </PanelItem>
            <PanelItem :label="$t('Password Login')" :description="$t('Enable users to login with email and password.')" :wrap="false">
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
  AutoSaveFormSwitchField, AutoSaveFormImageField,
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
  enableExplorePage: boolean
  primaryColorForeground: string | null
  primaryColorBackground: string | null
  primaryColorDarkForeground: string | null
  primaryColorDarkBackground: string | null
  logo: string | null
}>()

const form = useAutoSaveForm(() => ({
  platform_name: props.platformName || '',
  platform_locale: props.platformLocale,
  primary_color_foreground: props.primaryColorForeground || '',
  primary_color_background: props.primaryColorBackground || '',
  primary_color_dark_foreground: props.primaryColorDarkForeground || '',
  primary_color_dark_background: props.primaryColorDarkBackground || '',
  enable_registration: props.enableRegistration,
  enable_invitations: props.enableInvitations,
  enable_single_sign_on: props.enableSingleSignOn,
  enable_password_login: props.enablePasswordLogin,
  enable_explore_page: props.enableExplorePage,
  logo: props.logo,
}), {
  method: 'patch',
  url: route('admin.settings.update')
})
</script>
