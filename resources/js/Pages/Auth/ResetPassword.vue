<template>
  <Head title="Reset Password" />

  <GuestLayout>
    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
      <div class="flex flex-col space-y-2 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">
          Reset password
        </h1>
      </div>

      <form @submit.prevent="submit" class="grid gap-5">
        <FormControl label="E-Mail" :error="form.errors.email" for="email">
          <Input v-model="form.email" autocomplete="username" type="email" required autofocus id="email" />
        </FormControl>

        <FormControl label="Password" :error="form.errors.password" for="password">
          <Input v-model="form.password" autocomplete="new-password" type="password" autofocus required id="password" />
        </FormControl>

        <FormControl label="Confirm Password" :error="form.errors.password_confirmation" for="password_confirmation">
          <Input v-model="form.password_confirmation" autocomplete="new-password" type="password" required id="password_confirmation" />
        </FormControl>

        <Button :processing="form.processing">Reset Password</Button>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { GuestLayout } from "@/Layouts";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Button } from "@/Components/Button";

const props = defineProps<{
  email: string;
  token: string;
}>()

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: ''
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    }
  })
}
</script>
