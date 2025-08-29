<template>
  <StudioLayout>
    <div class="flex flex-1 flex-col">
      <div class="h-14 flex flex-row items-center justify-between px-4 relative border-b">
        <TabsNavigation :menu="menu" />

        <div class="inline-flex flex-row  bottom-3 right-4 gap-4">
          <Button @click="destroy" variant="ghost-desctructive" :label="$t('Discard draft')" />
          <Button :icon="CheckIcon" :label="$t('Publish')" />
        </div>
      </div>

      <slot />
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { TabsNavigation } from "@/Components/Tabs";
import { StudioLayout } from "@/Layouts"
import type { AppPageProps } from "@/Types";
import { usePage } from "@inertiajs/vue3";
import { asyncRouter, useNavigation } from "@stacktrace/ui";
import { trans, wTrans } from "laravel-vue-i18n";
import { computed } from "vue";
import { CheckIcon } from 'lucide-vue-next'

const generalTitle = wTrans('General')
const contentTitle = wTrans('Content')

const page = usePage<AppPageProps & {
  id: string
  status: 'draft' | 'publishing' | 'published' | 'unpublished'
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

const { confirm } = useConfirmable()
const destroy = () => confirm(
  trans(page.props.status === 'draft' ? 'Are you sure you want to delete this draft course?' : 'Are you sure you want to delete this course? All lessons and statistics will be permanently deleted.'),
  async () => {
    await asyncRouter.delete(route('studio.courses.destroy', [page.props.id]))
  },
  { title: trans(page.props.status === 'draft' ? 'Discard draft' : 'Delete course'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true }
)
</script>
