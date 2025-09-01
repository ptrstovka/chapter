<template>
  <StudioLayout>
    <div class="flex flex-1 flex-col">
      <div class="h-14 flex flex-row items-center justify-between px-3 relative border-b">
        <TabsNavigation :menu="menu" />

        <div class="inline-flex flex-row  bottom-3 right-4 gap-4">
          <Button v-if="course.canBeDeleted" :disabled="!course.canDelete" @click="destroy" variant="ghost-desctructive" :label="$t(course.status === 'draft' ? 'Discard draft' : 'Delete')" />

          <Button v-if="course.isPublishing" class="hover:bg-primary">
            <Spinner class="size-3.5" />

            {{ $t('Publishing…') }}
          </Button>

          <DropdownMenu v-else-if="course.canBeUnpublished">
            <DropdownMenuTrigger as-child>
              <Button class="relative" plain>
                <CheckIcon />
                {{ $t('Published') }}
                <ChevronDownIcon class="ml-3" />
                <div class="top-0 bottom-0 w-px bg-primary-foreground/30 right-8 absolute"></div>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="min-w-40" align="end">
              <DropdownMenuItem @select="unpublish" :disabled="!course.canUnpublish">{{ $t('Unpublish') }}</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <Button
            v-else
            :disabled="!course.canBePublished || !course.canPublish"
            :icon="CheckIcon"
            :label="$t('Publish')"
            @click="publish"
          />
        </div>
      </div>

      <slot />
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from "@/Components/DropdownMenu";
import { Spinner } from "@/Components/Spinner";
import { TabsNavigation } from "@/Components/Tabs";
import { StudioLayout } from "@/Layouts"
import type { AppPageProps } from "@/Types";
import { usePage } from "@inertiajs/vue3";
import { asyncRouter, useNavigation } from "@stacktrace/ui";
import { trans, wTrans } from "laravel-vue-i18n";
import { computed } from "vue";
import { CheckIcon, ChevronDownIcon } from 'lucide-vue-next'

const generalTitle = wTrans('General')
const contentTitle = wTrans('Content')

const page = usePage<AppPageProps & {
  id: string
  status: 'draft' | 'publishing' | 'published' | 'unpublished'
  title: string | null
  isPublishing: boolean
  canBePublished: boolean
  canPublish: boolean
  canBeUnpublished: boolean
  canUnpublish: boolean
  canDelete: boolean
  canBeDeleted: boolean
}>()

const course = computed(() => page.props)

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

const unpublish = () => confirm(
  trans(`Are you sure you want to unpublish this course? Your audience won't be able to access this course anymore.`),
  async () => await asyncRouter.post(route('studio.courses.unpublish', course.value.id), {}, { preserveScroll: true }),
  { title: trans('Unpublish Course'), confirmLabel: trans('Unpublish'), destructive: true }
)

const publish = () => confirm(
  trans(`Are you sure you want to publish this course? The course will be accessible to your audience.`),
  async () => await asyncRouter.post(route('studio.courses.publish', course.value.id), {}, { preserveScroll: true }),
  { title: trans('Publish Course'), confirmLabel: trans('Publish') }
)
</script>
