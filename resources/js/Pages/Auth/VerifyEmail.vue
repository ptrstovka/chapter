<template>
  <Head :title="$t('Email Verification')" />

  <GuestLayout>
    <LinkButton method="post" as="button" :href="route('logout')" variant="ghost" class="absolute right-4 top-4 md:right-8 md:top-8">
      {{ $t('Log Out') }}
    </LinkButton>

    <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
      <Alert v-if="verificationLinkSent" variant="positive">
        <AlertDescription>
          {{ $t('A new verification link has been sent to the email address you provided during registration.') }}
        </AlertDescription>
      </Alert>

      <div class="flex flex-col space-y-2 text-center">
        <p class="text-sm text-muted-foreground">
          {{ $t("Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.") }}
        </p>
      </div>

      <form @submit.prevent="submit" class="flex justify-center">
        <Button :processing="form.processing">{{ $t('Resend Verification Email') }}</Button>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { GuestLayout } from "@/Layouts";
import { Button, LinkButton } from "@/Components/Button";
import { Alert, AlertDescription } from "@/Components/Alert";

const props = defineProps<{
  status?: string;
}>()

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>
