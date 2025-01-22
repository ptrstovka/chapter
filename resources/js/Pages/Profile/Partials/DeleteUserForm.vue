<template>
  <Card>
    <CardHeader>
      <CardTitle>Delete Account</CardTitle>
      <CardDescription>
        Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting
        your account, please download any data or information that you wish to retain.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <ActionButton @click="confirmModal.activate" variant="destructive">Delete Account</ActionButton>

      <AlertDialog :control="confirmModal">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure you want to delete your account?</AlertDialogTitle>
            <AlertDialogDescription>
              Once your account is deleted, all of its resources and data will be permanently deleted. Please
              enter your password to confirm you would like to permanently delete your account.
            </AlertDialogDescription>
          </AlertDialogHeader>

          <FormControl :error="form.errors.password">
            <Input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Password"
              @keyup.enter="deleteUser"
            />
          </FormControl>

          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>

            <ActionButton @click="deleteUser" variant="destructive" :processing="form.processing">Delete Account</ActionButton>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ActionButton } from '@/Components/Button'
import { Card, CardContent, CardTitle, CardHeader, CardDescription } from '@/Components/Card'
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel  } from '@/Components/AlertDialog'
import { FormControl } from "@/Components/Form"
import { Input } from "@/Components/Input"
import { useForm } from '@inertiajs/vue3'
import { useToggle } from '@stacktrace/ui'

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
