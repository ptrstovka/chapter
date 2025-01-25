import { useToggle } from "@/Composables";
import { computed, ref } from "vue";

export { default as ConfirmationDialog } from './ConfirmationDialog.vue'

export declare type Callback = (() => any) | (() => Promise<any>)

export interface Confirmation {
  type: 'confirmation'
  title?: string | undefined
  message?: string | undefined
  cancelLabel?: string | undefined
  confirmLabel?: string | undefined
  cancel?: Callback | undefined
  confirm?: Callback | undefined
  destructive?: boolean | undefined
}

export interface Alert {
  type: 'alert'
  title?: string | undefined
  message?: string | undefined
  confirmLabel?: string | undefined
  confirm?: Callback | undefined
  destructive?: boolean | undefined
}

export type PendingDialog = Confirmation | Alert

const control = useToggle()
const pendingDialog = ref<PendingDialog | null>(null)

export function useConfirmationDialogRoot() {
  const dialog = computed(() => pendingDialog.value)

  const close = (delay: number = 0) => {
    control.deactivate()

    if (delay) {
      setTimeout(() => {
        pendingDialog.value = null
      }, delay)
    } else {
      pendingDialog.value = null
    }
  }

  return {
    control,
    dialog,
    close,
  }
}

export function useConfirmable() {
  const showConfirmDialog = (confirm: PendingDialog) => {
    pendingDialog.value = confirm
    control.activate()
  }

  function confirm(confirm: string, action?: Callback | undefined, options?: Partial<Confirmation>): void;
  function confirm(confirm: Confirmation): void;
  function confirm(confirm: string|Confirmation, action?: Callback | undefined, options?: Partial<Confirmation>) {
    if (typeof confirm == 'string') {
      showConfirmDialog({
        type: 'confirmation',
        ...(options || {}),
        message: confirm,
        confirm: action,
      })
    } else {
      showConfirmDialog(confirm as Confirmation)
    }
  }

  const confirmDestructive = (message: string, action?: Callback | undefined) => {
    return confirm(message, action, {
      destructive: true,
    })
  }

  function alert(message: string, action?: Callback | undefined, options?: Partial<Alert>): void;
  function alert(message: Alert): void;
  function alert(message: string|Alert, action?: Callback | undefined, options?: Partial<Alert>) {
    if (typeof message == 'string') {
      showConfirmDialog({
        type: 'alert',
        ...(options || {}),
        message,
        confirm: action,
      })
    } else {
      showConfirmDialog(message as Alert)
    }
  }

  const alertDestructive = (message: string, action?: Callback | undefined) => {
    return alert(message, action, {
      destructive: true,
    })
  }

  return {
    confirm, confirmDestructive, alert, alertDestructive,
  }
}
