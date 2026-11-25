<template>
  <CourseContentLayout :editor="editor">
    <div>
      <div class="flex flex-row justify-between items-center border-b px-3 h-10">
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbPage>
              {{ chapter.title || chapter.fallbackTitle }}
            </BreadcrumbPage>
          </BreadcrumbList>
        </Breadcrumb>

        <div class="inline-flex flex-row">
          <DropdownMenu>
            <DropdownMenuTrigger>
              <Button :icon="EllipsisIcon" variant="ghost" class="px-2" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="min-w-48">
              <DropdownMenuItem :disabled="!chapter.isEditable" @select="destroy" variant="destructive"><Trash2Icon /> {{ $t('Delete') }}</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <div class="flex flex-col gap-4 p-3">
        <FormControl :label="$t('Title')" :error="form.errors.title" class="max-w-lg">
          <Input v-model="form.title" :placeholder="chapter.fallbackTitle" :disabled="!chapter.isEditable" />
        </FormControl>
      </div>
    </div>

    <template #footer>
      <div class="flex-1 flex flex-row justify-end items-center">
        <Button
          :icon="SaveIcon"
          :label="$t('Save changes')"
          @click="save"
          :processing="form.processing"
          :recently-successful="form.recentlySuccessful"
          :disabled="!chapter.isEditable || !form.isDirty || form.processing"
        />
      </div>
    </template>
  </CourseContentLayout>
</template>

<script setup lang="ts">
import type { AppPageProps } from "@/Types"
import {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbPage,
} from "@/Components/Breadcrumb";
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/DropdownMenu";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { useSaveShortcut } from "@/Composables/useKeyboard.ts";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { EllipsisIcon, SaveIcon, Trash2Icon } from "lucide-vue-next";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";
import { useCourseEditorForm } from './useCourseEditorForm'

const props = defineProps<{
  id: string
  chapter: {
    id: string
    title: string | null
    fallbackTitle: string
    isEditable: boolean
  }
}>()

const form = useForm(() => ({
  title: props.chapter.title || '',
}))
const page = usePage<AppPageProps & { revision: number }>()
const { editor, save } = useCourseEditorForm(
  form,
  () => route('studio.course.chapters.update', [props.id, props.chapter.id]),
  () => props.chapter.isEditable,
)

useSaveShortcut(() => save())

const { confirm } = useConfirmable()
const destroy = () => confirm(trans('Remove this chapter and its lessons from the course editor?'), () => {
  if (!page.props.errors.revision) {
    router.delete(route('studio.course.chapters.destroy', [props.id, props.chapter.id]), { data: { revision: page.props.revision } })
  }
}, { title: trans('Delete Chapter'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
</script>
