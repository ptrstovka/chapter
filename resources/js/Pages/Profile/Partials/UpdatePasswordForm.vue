<template>
  <Card>
    <CardHeader>
      <CardTitle>Update Password</CardTitle>
      <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="updatePassword" class="flex flex-col items-start gap-6 max-w-xl">
        <FormControl class="w-full" label="Current Password" for="current_password" :error="form.errors.current_password">
          <Input
            id="current_password"
            v-model="form.current_password"
            type="password"
            autocomplete="current-password"
          />
        </FormControl>

        <FormControl class="w-full" label="New Password" for="password" :error="form.errors.password">
          <Input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
          />
        </FormControl>

        <FormControl class="w-full" label="Confirm Password" for="password_confirmation" :error="form.errors.password_confirmation">
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
          />
        </FormControl>

        <ActionButton :recently-successful="form.recentlySuccessful" :processing="form.processing">Save</ActionButton>
      </form>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardTitle, CardHeader, CardDescription } from '@/Components/Card'
import { ActionButton } from "@/Components/Button"
import { FormControl } from "@/Components/Form"
import { Input } from "@/Components/Input"
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation');
      }
      if (form.errors.current_password) {
        form.reset('current_password');
      }
    },
  });
};
</script>
