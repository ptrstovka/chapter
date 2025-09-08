import { computed, ref } from "vue";

const customAppName = ref<string | null>()

export function useAppTitle() {
  const defaultAppName = ref<string | undefined>((document.querySelector("meta[name=appName]") as HTMLMetaElement | undefined)?.content)

  const appName = computed(() => customAppName.value || defaultAppName.value)

  return (title: string) => {
    const name = appName.value

    return name ? `${title} - ${name}` : title
  }
}

export function setAppName(name: string | null) {
  customAppName.value = name
}
