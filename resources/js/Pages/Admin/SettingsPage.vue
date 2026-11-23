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
            <PanelTitle>{{ $t('Videos') }}</PanelTitle>
          </PanelHeader>
          <PanelContent>
            <PanelItem :label="$t('Generate HLS streams')" :description="$t('Automatically adapt video quality to the viewer’s connection speed.')" :wrap="false">
              <AutoSaveFormSwitchField name="enable_video_hls" />
            </PanelItem>
            <template v-if="form.value.enable_video_hls">
              <PanelItem :label="$t('Keep original videos')" :description="$t('Keep the original uploaded videos too. Turn this off to save storage space if you no longer need them.')" :wrap="false">
                <AutoSaveFormSwitchField name="keep_original_videos" />
              </PanelItem>
              <PanelItem :label="$t('Video qualities')" :description="$t('Choose which video qualities will be available for playback.')">
                <AutoSaveFormField name="video_qualities" v-slot="{ field, error }">
                  <div class="space-y-2">
                    <div class="flex flex-wrap gap-x-4 gap-y-3">
                      <label v-for="quality in qualities" :key="quality.value" class="inline-flex items-center gap-2 text-sm">
                        <Checkbox :model-value="field.value.includes(quality.value)" :disabled="field.value.length === 1 && field.value.includes(quality.value)" @update:model-value="checked => field.updateAndSave(checked ? [...field.value, quality.value].sort((a, b) => b - a) : field.value.filter((value: number) => value !== quality.value))" />
                        {{ quality.label }}
                      </label>
                    </div>
                    <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>
                  </div>
                </AutoSaveFormField>
              </PanelItem>
            </template>
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
            <PanelItem
              :label="$t('Studio User Directory')"
              :description="$t('Allow course creators to search users by name or email when granting course access.')"
              :wrap="false"
            >
              <AutoSaveFormSwitchField name="enable_studio_user_directory" />
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
  AutoSaveFormField,
} from "@/Components/AutoSaveForm";
import { AdminLayout } from "@/Layouts";
import { Panel, PanelHeader, PanelTitle, PanelContent, PanelItem } from '@/Components/Panel'
import type { SelectOption } from "@stacktrace/ui";
import { Checkbox } from '@/Components/Checkbox'

const qualities = [
  { value: 2160, label: '4K' }, { value: 1440, label: '2K' }, { value: 1080, label: '1080p' },
  { value: 720, label: '720p' }, { value: 480, label: '480p' }, { value: 360, label: '360p' },
]

const props = defineProps<{
  platformName: string | null
  platformLocale: string
  availableLocales: Array<SelectOption>
  enableRegistration: boolean
  enableInvitations: boolean
  enableSingleSignOn: boolean
  enablePasswordLogin: boolean
  enableStudioUserDirectory: boolean
  enableExplorePage: boolean
  enableVideoHls: boolean
  keepOriginalVideos: boolean
  videoQualities: number[]
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
  enable_studio_user_directory: props.enableStudioUserDirectory,
  enable_explore_page: props.enableExplorePage,
  enable_video_hls: props.enableVideoHls,
  keep_original_videos: props.keepOriginalVideos,
  video_qualities: props.videoQualities,
  logo: props.logo,
}), {
  method: 'patch',
  url: route('admin.settings.update')
})
</script>
