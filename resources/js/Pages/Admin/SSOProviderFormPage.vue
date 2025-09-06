<template>
  <AdminLayout :title="$t('Create SSO Provider')">
    <div class="flex flex-col divide-y">
      <Panel v-if="provider">
        <PanelHeader>
          <PanelTitle>{{ $t('SSO Provider') }}</PanelTitle>
        </PanelHeader>
        <PanelContent>
          <PanelItem :label="$t('Callback URL')">
            <code class="text-xs">{{ provider.callbackUrl }}</code>
          </PanelItem>

          <PanelItem :label="$t('Active')" :wrap="false">
            <SwitchToggle :value="provider.isActive" field="is_active" method="post" :url="route('admin.sso.activate', provider.id)" />
          </PanelItem>
        </PanelContent>
      </Panel>

      <Panel>
        <PanelHeader v-if="provider">
          <PanelTitle>{{ $t('Configuration') }}</PanelTitle>
        </PanelHeader>

        <PanelContent>
          <PanelItem v-if="!provider" :label="$t('Type')">
            <FormControl :error="form.errors.type">
              <FormSelect :options="types" v-model="form.type" class="w-full sm:max-w-md" />
            </FormControl>
          </PanelItem>

          <PanelItem :label="$t('Provider Name')">
            <FormControl :error="form.errors.name">
              <Input v-model="form.name" class="sm:max-w-md" />
            </FormControl>
          </PanelItem>

          <template v-if="form.type === 'custom'">
            <PanelItem :label="$t('Client ID')">
              <FormControl :error="form.errors.client_id">
                <Input v-model="form.client_id" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Client Secret')">
              <FormControl :error="form.errors.client_secret">
                <Input v-model="form.client_secret" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Authorize URL')">
              <FormControl :error="form.errors.authorize_url">
                <Input v-model="form.authorize_url" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Token URL')">
              <FormControl :error="form.errors.token_url">
                <Input v-model="form.token_url" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('User URL')">
              <FormControl :error="form.errors.user_url">
                <Input v-model="form.user_url" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Request Parameters')" :center="false">
              <FormControl :error="form.errors.request_parameters">
                <KeyValueInput v-model="form.request_parameters" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('User E-Mail field')">
              <FormControl :error="form.errors.user_email_field">
                <Input v-model="form.user_email_field" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('User Name field')">
              <FormControl :error="form.errors.user_name_field">
                <Input v-model="form.user_name_field" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('User Avatar field')">
              <FormControl :error="form.errors.user_avatar_field">
                <Input v-model="form.user_avatar_field" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Login Button Title')">
              <FormControl :error="form.errors.login_button_title">
                <Input v-model="form.login_button_title" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Login Button Text Color')">
              <FormControl :error="form.errors.login_button_text_color">
                <Input v-model="form.login_button_text_color" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>

            <PanelItem :label="$t('Login Button Background Color')">
              <FormControl :error="form.errors.login_button_background_color">
                <Input v-model="form.login_button_background_color" class="sm:max-w-md" />
              </FormControl>
            </PanelItem>
          </template>
        </PanelContent>

        <PanelFooter class="flex justify-end gap-4">
          <Button v-if="provider" @click="destroy" variant="ghost-desctructive" :label="$t('Delete')" />

          <Button :recently-successful="form.recentlySuccessful" :processing="form.processing" @click="save" :label="$t(provider ? 'Save' : 'Create')" />
        </PanelFooter>
      </Panel>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { FormControl, FormSelect } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { SwitchToggle } from "@/Components/Switch";
import { AdminLayout } from "@/Layouts";
import { useForm } from "@inertiajs/vue3";
import { asyncRouter, type SelectOption } from "@stacktrace/ui";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";
import { KeyValueInput } from '@/Components/KeyValueInput'

const types = computed<Array<SelectOption>>(() => [
  { label: trans('Custom'), value: 'custom' },
])

interface Provider {
  id: string
  name: string
  type: string
  configuration: Record<string, any>
  callbackUrl: string
  isActive: boolean
}

const props = defineProps<{
  provider?: Provider
}>()

const form = useForm(() => ({
  type: props.provider?.type || 'custom',
  name: props.provider?.name || '',
  client_id: props.provider?.configuration.client_id || '',
  client_secret: props.provider?.configuration.client_secret || '',
  authorize_url: props.provider?.configuration.authorize_url || '',
  token_url: props.provider?.configuration.token_url || '',
  user_url: props.provider?.configuration.user_url || '',
  request_parameters: props.provider?.configuration.request_parameters || {},
  user_id_field: props.provider?.configuration.user_id_field || '',
  user_email_field: props.provider?.configuration.user_email_field || '',
  user_name_field: props.provider?.configuration.user_name_field || '',
  user_avatar_field: props.provider?.configuration.user_avatar_field || '',
  login_button_title: props.provider?.configuration.login_button_title || '',
  login_button_text_color: props.provider?.configuration.login_button_text_color || '',
  login_button_background_color: props.provider?.configuration.login_button_background_color || '',
}))

const save = () => {
  const method = props.provider ? 'patch' : 'post'
  const url = props.provider ? route('admin.sso.update', props.provider.id) : route('admin.sso.store')

  form.submit(method, url, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(
  trans('Are you sure you want to delete this SSO provider?'),
  async () => {
    const id = props.provider?.id
    if (id) {
      await asyncRouter.delete(route('admin.sso.destroy', id))
    }
  },
  { title: trans('Delete SSO Provider'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true },
)
</script>
