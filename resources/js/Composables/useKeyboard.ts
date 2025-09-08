import { useMagicKeys } from "@vueuse/core";
import { watch } from "vue";

export function useSaveShortcut(callback: () => void) {
  const { Meta_S, Ctrl_S } = useMagicKeys({
    passive: false,
    onEventFired(e) {
      if (e.key === "s" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
      }
    },
  })

  watch([Meta_S, Ctrl_S], (v) => {
    if (v[0] || v[1]) {
      callback()
    }
  })
}
