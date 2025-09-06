import { usePage } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { toast } from 'vue-sonner'

const sent = new Map<string, boolean>()

export interface BackendToast {
  id: string
  title: string
  content: string | null
  variant: 'default' | 'destructive'
}

export function useBackendSonner() {
  const page = usePage()

  const flash = (toasts: Array<BackendToast>) => {
    toasts.forEach(message => {
      if (sent.has(message.id)) {
        return
      }

      toast(message.title, {
        description: message.content || undefined,
      })

      sent.set(message.id, true)
    })
  }


  const toasts = computed(() => page.props.toasts)
  flash(toasts.value)
  watch(toasts, toasts => flash(toasts))
}
