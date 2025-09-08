<template>
  <Head :title="$t('Confirm Password')" />

  <GuestLayout
    :title="$t('Confirm Password')"
    :description="$t('This is a secure area of the application. Please confirm your password before continuing.')"
  >
    <div class="mx-auto flex w-full flex-col justify-center space-y-6">
      <form @submit.prevent="submit" class="grid gap-5">
        <FormControl :label="$t('Password')" :error="form.errors.password" for="password">
          <Input v-model="form.password" autocomplete="current-password" type="password" required id="password" />
        </FormControl>

        <Button :processing="form.processing">{{ $t('Confirm') }}</Button>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import { GuestLayout } from "@/Layouts";
import { FormControl} from "@/Components/Form";
import { Input } from '@/Components/Input';
import { Button } from "@/Components/Button";

const form = useForm({
  password: ''
})

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => {
      form.reset()
    }
  })
}
</script>
