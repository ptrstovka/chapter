<template>
  <Head :title="$t('Register')" />

  <GuestLayout :title="$t('Create an account')">
    <Button :as="Link" :href="route('login')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      {{ $t('Login') }}
    </Button>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6">
      <form @submit.prevent="submit" class="grid gap-5">
        <FormControl :label="$t('Name')" :error="form.errors.name" for="name">
          <Input v-model="form.name" autocomplete="name" required autofocus id="name" />
        </FormControl>

        <FormControl :label="$t('E-Mail')" :error="form.errors.email" for="email">
          <Input v-model="form.email" autocomplete="username" type="email" required id="email" />
        </FormControl>

        <FormControl :label="$t('Password')" :error="form.errors.password" for="password">
          <Input v-model="form.password" autocomplete="new-password" type="password" required id="password" />
        </FormControl>

        <FormControl :label="$t('Confirm Password')" :error="form.errors.password_confirmation" for="password_confirmation">
          <Input v-model="form.password_confirmation" autocomplete="new-password" type="password" required id="password_confirmation" />
        </FormControl>

        <FormControl :label="$t('Invitation Code')" :error="form.errors.invitation" for="invitation">
          <Input v-model="form.invitation" required id="invitation" />
        </FormControl>

        <Button :processing="form.processing">{{ $t('Register') }}</Button>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import { GuestLayout } from "@/Layouts";
import { Button } from "@/Components/Button";
import { Input } from '@/Components/Input';
import { FormControl } from "@/Components/Form";

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  invitation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    }
  })
}
</script>
