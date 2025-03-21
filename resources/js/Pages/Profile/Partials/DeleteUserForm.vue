<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ $t('Delete Account') }}</CardTitle>
      <CardDescription>
        {{ $t('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
      </CardDescription>
    </CardHeader>
    <CardContent>
      <Button @click="confirmModal.activate" variant="destructive">{{ $t('Delete Account') }}</Button>

      <AlertDialog :control="confirmModal">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{{ $t('Are you sure you want to delete your account?') }}</AlertDialogTitle>
            <AlertDialogDescription>
              {{ $t('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </AlertDialogDescription>
          </AlertDialogHeader>

          <FormControl :error="form.errors.password">
            <Input
              id="password"
              v-model="form.password"
              type="password"
              :placeholder="$t('Password')"
              @keyup.enter="deleteUser"
            />
          </FormControl>

          <AlertDialogFooter>
            <AlertDialogCancel>{{ $t('Cancel') }}</AlertDialogCancel>

            <Button @click="deleteUser" variant="destructive" :processing="form.processing">{{ $t('Delete Account') }}</Button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Card, CardContent, CardTitle, CardHeader, CardDescription } from '@/Components/Card'
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel  } from '@/Components/AlertDialog'
import { FormControl } from "@/Components/Form"
import { Input } from "@/Components/Input"
import { useForm } from '@inertiajs/vue3'
import { useToggle } from '@/Composables'

const confirmModal = useToggle()

const form = useForm({
  password: '',
})

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => confirmModal.deactivate(),
    onFinish: () => {
      form.reset();
    },
  })
}
</script>
