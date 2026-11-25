<template>
  <CourseContentLayout :editor="editor">
    <div>
      <div class="flex flex-row justify-between items-center border-b px-3 h-10">
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink :href="route(courseRouteName('course.chapters.show'), [id, chapter.id])">{{ chapter.title || chapter.fallbackTitle }}</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbPage>
              {{ lesson.displayTitle }}
            </BreadcrumbPage>
          </BreadcrumbList>
        </Breadcrumb>

        <div class="inline-flex flex-row">
          <DropdownMenu>
            <DropdownMenuTrigger>
              <Button :icon="EllipsisIcon" variant="ghost" class="px-2" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="min-w-48">
              <DropdownMenuItem :disabled="disabled" @select="destroy" variant="destructive"><Trash2Icon /> {{ $t('Delete') }}</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <div class="flex flex-col gap-4 p-3">
        <FormControl :label="$t('Title')" :error="form.errors.title" class="max-w-lg">
          <Input v-model="form.title" :placeholder="$t('Untitled')" :disabled="disabled" />
        </FormControl>

        <FormControl :label="$t('Video')" class="max-w-lg">
          <VideoInput :course-id="id" :lesson-id="lesson.id" :source="lesson.video" :label="lesson.displayTitle" :disabled="disabled" />
        </FormControl>

        <FormControl :label="$t('Description')" :error="form.errors.description || form.errors.description_type">
          <TextEditor
            v-model:content="form.description"
            v-model:content-type="form.description_type"
            :disabled="disabled"
          />
        </FormControl>

        <FormControl :label="$t('Resources')" :error="form.errors.resources" class="max-w-lg">
          <FileListInput
            class="data-[empty]:aspect-video data-[empty]:p-0"
            scope="CourseResource"
            v-model="form.resources"
            :drag-label="$t('Drag and drop files')"
            :pick-label="$t('select files')"
            :disabled="disabled"
          />
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
          :disabled="disabled || !form.isDirty || form.processing"
        />
      </div>
    </template>
  </CourseContentLayout>
</template>

<script setup lang="ts">
import { useCourseEditorRoutes } from '@/Composables/useCourseEditorRoutes'
import type { AppPageProps } from "@/Types"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList, BreadcrumbPage,
  BreadcrumbSeparator
} from "@/Components/Breadcrumb";
import { Button } from "@/Components/Button";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuItem } from "@/Components/DropdownMenu";
import { FileListInput, type FileListItem } from "@/Components/FileListInput";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import VideoInput from "@/Components/VideoUpload/VideoInput.vue";
import { TextEditor } from "@/Components/TextEditor";
import { useSaveShortcut } from "@/Composables/useKeyboard.ts";
import type { TextContentType, VideoSource } from "@/Types";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";
import { useCourseEditorForm } from './useCourseEditorForm'
import { Trash2Icon, EllipsisIcon, SaveIcon } from 'lucide-vue-next'

const { courseRouteName } = useCourseEditorRoutes()

const props = defineProps<{
  id: string
  chapter: {
    id: string
    title: string | null
    fallbackTitle: string
  }
  lesson: {
    id: string
    title: string | null
    displayTitle: string
    description: string | null
    descriptionType: TextContentType
    video: VideoSource | null
    resources: Array<FileListItem>
    isEditable: boolean
  }
}>()

const disabled = computed(() => !props.lesson.isEditable)

const form = useForm(() => ({
  title: props.lesson.title || '',
  description: props.lesson.description || '',
  description_type: props.lesson.descriptionType,
  resources: props.lesson.resources.map(it => ({...it})),
}))

const page = usePage<AppPageProps & { revision: number }>()
const { editor, save } = useCourseEditorForm(
  form,
  () => route(courseRouteName('course.lessons.update'), [props.id, props.chapter.id, props.lesson.id]),
  () => !disabled.value,
)

useSaveShortcut(() => save())

const { confirm } = useConfirmable()
const destroy = () => confirm(trans('Remove this lesson from the course editor?'), () => {
  if (!page.props.errors.revision) {
    router.delete(route(courseRouteName('course.lessons.destroy'), [props.id, props.chapter.id, props.lesson.id]), { data: { revision: page.props.revision } })
  }
}, { title: trans('Delete Lesson'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
</script>
