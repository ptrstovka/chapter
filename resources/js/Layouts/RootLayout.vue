<template>
  <ConfigProvider :scroll-body="false" :locale="locale">
    <slot />

    <Toaster />
    <ConfirmationDialog />
  </ConfigProvider>
</template>

<script setup lang="ts">
import { setAppName } from "@/Composables";
import { ConfirmationDialog } from '@/Components/ConfirmationDialog'
import { usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import { loadLanguageAsync } from 'laravel-vue-i18n'
import { Toaster } from '@/Components/Sonner'
import { ConfigProvider } from 'reka-ui'
import 'vue-sonner/style.css'

const page = usePage()
const locale = computed(() => page.props.app.locale)
const appName = computed(() => page.props.app.name)

watch(locale, updatedLocale => loadLanguageAsync(updatedLocale))
watch(appName, updatedAppName => setAppName(updatedAppName))
</script>
