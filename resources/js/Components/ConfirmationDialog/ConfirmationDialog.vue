<template>
  <AlertDialog :control="control">
    <AlertDialogContent v-if="dialog">
      <AlertDialogHeader>
        <AlertDialogTitle>{{ dialog.title || (dialog.type === 'confirmation' ? 'Confirmation' : 'Warning') }}</AlertDialogTitle>
        <AlertDialogDescription>{{ dialog.message || 'Are you sure you want to run this action?' }}</AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <template v-if="dialog.type === 'confirmation'">
          <Button @click="cancel" :processing="isCancelling" variant="outline">{{ dialog.cancelLabel || 'Cancel' }}</Button>
          <Button @click="confirm" :processing="isConfirming" :variant="dialog.destructive ? 'destructive' : 'default'">{{ dialog.confirmLabel || 'Confirm' }}</Button>
        </template>

        <template v-else-if="dialog.type === 'alert'">
          <Button @click="confirm" :processing="isConfirming" :variant="dialog.destructive ? 'destructive' : 'default'">{{ dialog.confirmLabel || 'OK' }}</Button>
        </template>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { useConfirmationDialogRoot } from "."
import { ref } from "vue"
import { onDeactivated } from "@/Composables"
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter } from '@/Components/AlertDialog'
import { Button } from '@/Components/Button'

const { control, dialog, close: closeDialog } = useConfirmationDialogRoot()

const isCancelling = ref(false)
const isConfirming = ref(false)

const ANIMATION_DELAY = 300

const close = () => closeDialog(ANIMATION_DELAY)

const cancel = async () => {
  const pendingDialog = dialog.value

  const cancelCallback = pendingDialog?.type === 'confirmation'
    ? pendingDialog.cancel
    : null

  if (! cancelCallback) {
    close()
    return
  }

  isCancelling.value = true

  await cancelCallback()
  close()
}

const confirm = async () => {
  const confirmCallback = dialog.value?.confirm

  if (! confirmCallback) {
    close()
    return
  }

  isConfirming.value = true

  await confirmCallback()
  close()
}

onDeactivated(control, () => {
  setTimeout(() => {
    isCancelling.value = false
    isConfirming.value = false
  }, ANIMATION_DELAY)
})
</script>
