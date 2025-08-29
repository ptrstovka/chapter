<template>
  <StudioLayout>
    <div class="p-4 max-w-7xl">
      <div class="flex flex-row justify-between items-end">
        <h4 class="text-base font-semibold mb-4">{{ page.props.title || $t('New Draft') }}</h4>

        <div class="inline-flex flex-row">
          <Button>{{ $t('Publish') }}</Button>
        </div>
      </div>

      <TabsNavigation :menu="menu" class="mb-4" />

      <slot />
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { TabsNavigation } from "@/Components/Tabs";
import { StudioLayout } from "@/Layouts"
import type { AppPageProps } from "@/Types";
import { usePage } from "@inertiajs/vue3";
import { useNavigation } from "@stacktrace/ui";
import { wTrans } from "laravel-vue-i18n";
import { computed } from "vue";

const generalTitle = wTrans('General')
const contentTitle = wTrans('Content')

const page = usePage<AppPageProps & {
  id: string
  title: string | null
}>()

const menu = useNavigation(computed(() => [
  {
    title: generalTitle.value,
    action: { route: 'studio.courses.show', params: [page.props.id] },
  },
  {
    title: contentTitle.value,
    action: { route: 'studio.courses.content', params: [page.props.id] }
  },
]))
</script>
