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

      <form @submit.prevent="submit" class="grid gap-5">
        <FormControl :label="$t('E-Mail')" :error="form.errors.email" for="email">
          <Input v-model="form.email" autocomplete="username" type="email" required autofocus id="email" />
        </FormControl>

        <FormControl :label="$t('Password')" :error="form.errors.password" for="password">
          <Input v-model="form.password" autocomplete="current-password" type="password" required id="password" />
        </FormControl>

        <FormControl>
          <Label class="flex items-center gap-2" for="remember">
            <Checkbox id="remember" v-model="form.remember" />
            {{ $t('Remember me') }}
          </Label>
        </FormControl>

        <Button :processing="form.processing">{{ $t('Log in') }}</Button>
      </form>

      <p v-if="canResetPassword" class="px-8 text-center text-sm text-muted-foreground">
        <Link
          :href="route('password.request')"
          class="underline underline-offset-4 hover:text-primary">
          {{ $t('Forgot your password?') }}
        </Link>
      </p>
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
