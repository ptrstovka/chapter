<template>
  <Head title="Register" />

  <GuestLayout>
    <LinkButton :href="route('login')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      Login
    </LinkButton>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
      <div class="flex flex-col space-y-2 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">
          Create an account
        </h1>
      </div>

      <form @submit.prevent="submit" class="grid gap-5">
        <FormControl label="Name" :error="form.errors.name" for="name">
          <Input v-model="form.name" autocomplete="name" required autofocus id="name" />
        </FormControl>

        <FormControl label="E-Mail" :error="form.errors.email" for="email">
          <Input v-model="form.email" autocomplete="username" type="email" required id="email" />
        </FormControl>

        <FormControl label="Password" :error="form.errors.password" for="password">
          <Input v-model="form.password" autocomplete="new-password" type="password" required id="password" />
        </FormControl>

        <FormControl label="Confirm Password" :error="form.errors.password_confirmation" for="password_confirmation">
          <Input v-model="form.password_confirmation" autocomplete="new-password" type="password" required id="password_confirmation" />
        </FormControl>

        <ActionButton :processing="form.processing">Register</ActionButton>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { GuestLayout } from "@/Layouts";
import { ActionButton, LinkButton } from "@/Components/Button";
import { Input } from '@/Components/Input';
import { FormControl } from "@/Components/Form";

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    }
  })
}
</script>
