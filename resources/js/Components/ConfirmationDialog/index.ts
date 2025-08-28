import { useToggle } from "@stacktrace/ui";
import { computed, ref } from "vue";

export { default as ConfirmationDialog } from './ConfirmationDialog.vue'

export declare type ConfirmationCallback = (() => any) | (() => Promise<any>)

export interface Confirmation {
  title?: string | undefined
  message?: string | undefined
  cancelLabel?: string | undefined
  confirmLabel?: string | undefined
  cancel?: ConfirmationCallback | undefined
  confirm?: ConfirmationCallback | undefined
  destructive?: boolean | undefined
}

const control = useToggle()
const confirmation = ref<Confirmation | null>(null)

export function useConfirmationDialogRoot() {
  const dialog = computed(() => confirmation.value)

  const close = (delay: number = 0) => {
    control.deactivate()

    if (delay) {
      setTimeout(() => {
        confirmation.value = null
      }, delay)
    } else {
      confirmation.value = null
    }
  }

  return {
    control,
    dialog,
    close,
  }
}

export function useConfirmable() {
  const showConfirmDialog = (confirm: Confirmation) => {
    confirmation.value = confirm
    control.activate()
  }

  function confirm(confirm: string, action?: ConfirmationCallback | undefined, options?: Partial<Confirmation>): void;
  function confirm(confirm: Confirmation): void;
  function confirm(confirm: string|Confirmation, action?: ConfirmationCallback | undefined, options?: Partial<Confirmation>) {
    if (typeof confirm == 'string') {
      showConfirmDialog({
        ...(options || {}),
        message: confirm,
        confirm: action,
      })
    } else {
      showConfirmDialog(confirm as Confirmation)
    }
  }

  const confirmDestructive = (message: string, action?: ConfirmationCallback | undefined) => {
    return confirm(message, action, {
      destructive: true,
    })
  }

  return {
    confirm, confirmDestructive,
  }
}
