<template>
  <Head :title="$t('Log in')" />

  <GuestLayout>
    <Button :as="Link" v-if="canRegister" :href="route('register')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      {{ $t('Register') }}
    </Button>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
      <Alert v-if="status" variant="positive">
        <AlertDescription>{{ status }}</AlertDescription>
      </Alert>

      <div class="flex flex-col space-y-2 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">
          {{ $t('Log in') }}
        </h1>
      </div>

      <form v-if="passwordLoginEnabled" @submit.prevent="submit" class="grid gap-5">
        <FormControl :label="$t('E-Mail')" :error="form.errors.email" for="email">
          <Input v-model="form.email" autocomplete="username" type="email" required autofocus id="email" />
        </FormControl>

        <FormControl :label="$t('Password')" :error="form.errors.password" for="password" class="relative">
          <Input v-model="form.password" autocomplete="current-password" type="password" required id="password" />

          <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="hover:text-primary text-sm text-muted-foreground absolute -top-[3px] right-0">
            {{ $t('Forgot your password?') }}
          </Link>
        </FormControl>

        <FormControl>
          <Label class="flex items-center gap-2" for="remember">
            <Checkbox id="remember" v-model="form.remember" />
            {{ $t('Remember me') }}
          </Label>
        </FormControl>

        <Button :processing="form.processing">{{ $t('Log in') }}</Button>
      </form>

      <template v-if="singleSignOnEnabled && singleSignOnProviders.length > 0">
        <p v-if="passwordLoginEnabled" class="text-center text-muted-foreground text-sm">{{ $t('or') }}</p>

        <div class="flex flex-col gap-4">
          <a
            v-for="provider in singleSignOnProviders"
            :href="provider.url"
            class="bg-(--provider) text-(--provider-foreground) text-center text-sm font-medium whitespace-nowrap outline-none shrink-0 rounded-md py-1.5"
            :style="{ '--provider-foreground': provider.textColor, '--provider': provider.backgroundColor }"
          >{{ provider.title }}</a>
        </div>
      </template>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { GuestLayout } from '@/Layouts'
import { Alert, AlertDescription } from "@/Components/Alert"
import { Button } from "@/Components/Button"
import { FormControl } from "@/Components/Form";
import { Input } from '@/Components/Input'
import { Label } from "@/Components/Label"
import { Checkbox } from "@/Components/Checkbox"
import { useForm, Head, Link } from '@inertiajs/vue3'

defineProps<{
  canResetPassword?: boolean;
  canRegister?: boolean;
  status?: string;
  passwordLoginEnabled: boolean
  singleSignOnEnabled: boolean
  singleSignOnProviders: Array<{
    url: string
    title: string
    textColor: string
    backgroundColor: string
  }>
}>()

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => {
      form.reset('password')
    }
  })
}
</script>
