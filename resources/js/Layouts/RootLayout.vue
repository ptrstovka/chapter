<template>
  <slot />

  <Toaster />
  <ConfirmationDialog />
</template>

<script setup lang="ts">
import { useSelectedTheme, setAppName } from '@/Composables'
import { ConfirmationDialog } from '@/Components/ConfirmationDialog'
import { usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import { loadLanguageAsync } from 'laravel-vue-i18n'
import { Toaster } from '@/Components/Sonner'
import 'vue-sonner/style.css'

useSelectedTheme()

const page = usePage()
const locale = computed(() => page.props.locale)
const appName = computed(() => page.props.appName)

watch(locale, updatedLocale => loadLanguageAsync(updatedLocale))
watch(appName, updatedAppName => setAppName(updatedAppName))
</script>
