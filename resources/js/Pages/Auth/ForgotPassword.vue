<template>
  <Head :title="$t('Forgot Password')" />

  <GuestLayout
    :title="$t('Forgot Password')"
    :description="$t('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')"
  >
    <Button :as="Link" v-if="canLogin" :href="route('login')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      {{ $t('Login') }}
    </Button>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6">
      <div class="grid gap-6">
        <form @submit.prevent="submit" class="grid gap-5">
          <FormControl :label="$t('E-Mail')" :error="form.errors.email" for="email">
            <Input v-model="form.email" autocomplete="username" type="email" required autofocus id="email" />
          </FormControl>

          <Button :processing="form.processing">{{ $t('Email Password Reset Link') }}</Button>
        </form>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import { GuestLayout } from '@/Layouts'
import { Button } from "@/Components/Button";
import { FormControl } from "@/Components/Form";
import { Input } from '@/Components/Input';
import { watch } from "vue";
import { toast } from "vue-sonner";

const props = defineProps<{
  status?: string
  canLogin?: boolean
}>()

const form = useForm({
  email: ''
})

const submit = () => {
  form.post(route('password.email'))
}

watch(() => props.status, status => {
  if (status) {
    setTimeout(() => {
      toast(status)
    }, 300)
  }
})
</script>
