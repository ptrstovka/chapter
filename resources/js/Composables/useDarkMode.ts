import { useLocalStorage, usePreferredDark } from "@vueuse/core";
import { type Ref, watch } from "vue";

const mode = useLocalStorage<'system' | 'dark' | 'light'>('HubTheme', 'system')

export function useDarkMode() {
  return {
    mode: mode as unknown as Ref<'system' | 'dark' | 'light'>
  }
}

export function useSelectedTheme() {
  const setTheme = (theme: 'light' | 'dark') => {
    if (theme == 'light') {
      if (document.body.classList.contains('dark')) {
        document.body.classList.remove('dark')
      }
    } else if (theme == 'dark') {
      if (! document.body.classList.contains('dark')) {
        document.body.classList.add('dark')
      }
    }
  }

  const prefersDark = usePreferredDark()

  const resolveTheme = () => {
    if (mode.value === 'system') {
      if (prefersDark.value) {
        return 'dark'
      }

      return 'light'
    }

    return mode.value
  }

  setTheme(resolveTheme())

  watch([mode, prefersDark], () => {
    setTheme(resolveTheme())
  })
}
