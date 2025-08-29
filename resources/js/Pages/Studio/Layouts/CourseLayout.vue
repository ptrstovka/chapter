<template>
  <StudioLayout>
    <div class="flex flex-1 flex-col">
      <div class="h-24 flex flex-col items-start justify-center px-4 relative pb-3 border-b">
        <div class="flex flex-row justify-between items-center flex-1">
          <h4 class="text-lg font-semibold">{{ page.props.title || $t('New Draft') }}</h4>
        </div>

        <TabsNavigation :menu="menu" />

        <div class="inline-flex flex-row absolute bottom-3 right-4">
          <Button>{{ $t('Publish') }}</Button>
        </div>
      </div>

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
    action: { route: 'studio.courses.content', params: [page.props.id] },
    active: [
      { route: 'studio.courses.content' },
      { route: 'studio.course.lessons*' },
      { route: 'studio.course.chapters*' },
    ]
  },
]))
</script>
