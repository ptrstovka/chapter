<template>
  <Card>
    <CardHeader>
      <CardTitle>Profile Information</CardTitle>
      <CardDescription>Update your account's profile information and email address.</CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="form.patch(route('profile.update'))" class="flex flex-col gap-6 items-start max-w-xl">
        <Alert v-show="status === 'verification-link-sent'" class="mt-2" variant="positive">
          <AlertDescription>
            A new verification link has been sent to your email address.
          </AlertDescription>
        </Alert>

        <FormControl label="Name" :error="form.errors.name" for="name" class="w-full">
          <Input v-model="form.name" autocomplete="name" required autofocus id="name" />
        </FormControl>

        <FormControl label="E-Mail" :error="form.errors.email" for="email" class="w-full">
          <Input v-model="form.email" autocomplete="username" autofocus id="email" />
        </FormControl>

        <div v-if="mustVerifyEmail && user.email_verified_at === null">
          <p class="text-sm mt-2">
            Your email address is unverified.
            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="underline text-muted-foreground hover:text-foreground transition-colors text-sm rounded-md focus:outline-none"
            >
              Click here to re-send the verification email.
            </Link>
          </p>
        </div>

        <Button :recently-successful="form.recentlySuccessful" :processing="form.processing">Save</Button>
      </form>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Alert, AlertDescription } from '@/Components/Alert'
import { Button } from "@/Components/Button"
import { FormControl } from "@/Components/Form"
import { Input } from "@/Components/Input"
import { Card, CardContent, CardTitle, CardHeader, CardDescription } from '@/Components/Card'
import { useForm, usePage, Link } from '@inertiajs/vue3'

defineProps<{
  mustVerifyEmail?: Boolean;
  status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
  name: user.name,
  email: user.email,
});
</script>
