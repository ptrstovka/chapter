<template>
  <Head title="Forgot Password" />

  <GuestLayout>
    <LinkButton v-if="canLogin" :href="route('login')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      Login
    </LinkButton>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
      <Alert v-if="status" variant="positive">
        <AlertDescription>{{ status }}</AlertDescription>
      </Alert>

      <div class="flex flex-col space-y-2 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">
          Forgot Password
        </h1>

        <p class="text-sm text-muted-foreground">
          Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
        </p>
      </div>

      <div class="grid gap-6">
        <form @submit.prevent="submit" class="grid gap-5">
          <FormControl label="E-Mail" :error="form.errors.email" for="email">
            <Input v-model="form.email" autocomplete="username" type="email" required autofocus id="email" />
          </FormControl>

          <ActionButton :processing="form.processing">Email Password Reset Link</ActionButton>
        </form>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { GuestLayout } from '@/Layouts'
import { LinkButton, ActionButton } from "@/Components/Button";
import { Alert, AlertDescription } from "@/Components/Alert";
import { FormControl } from "@/Components/Form";
import { Input } from '@/Components/Input';

defineProps<{
  status?: string
  canLogin?: boolean
}>()

const form = useForm({
  email: ''
})

const submit = () => {
  form.post(route('password.email'))
}
</script>
