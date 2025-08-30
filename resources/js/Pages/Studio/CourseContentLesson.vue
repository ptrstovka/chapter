<template>
  <CourseContentLayout>
    <div>
      <div class="flex flex-row justify-between items-center border-b px-3 h-10">
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink :href="route('studio.course.chapters.show', [id, chapter.id])">{{ chapter.title || chapter.fallbackTitle }}</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbPage>
              {{ lesson.title || lesson.fallbackTitle }}
            </BreadcrumbPage>
          </BreadcrumbList>
        </Breadcrumb>

        <div class="inline-flex flex-row">
          <DropdownMenu>
            <DropdownMenuTrigger>
              <Button :icon="EllipsisIcon" variant="ghost" class="px-2" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="min-w-48">
              <DropdownMenuItem @select="destroy" variant="destructive"><Trash2Icon /> {{ $t('Delete') }}</DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <div class="flex flex-col gap-4 p-3">
        <FormControl :label="$t('Title')" :error="form.errors.title" class="max-w-lg">
          <Input v-model="form.title" :placeholder="lesson.fallbackTitle" />
        </FormControl>

        <FormControl :label="$t('Video')" :error="form.errors.video" class="max-w-lg">
          <TemporaryFileInput
            class="aspect-video"
            scope="CourseVideo"
            :source="lesson.video"
            v-model:remove="form.remove_video"
            v-model:file="form.video"
            drag-label="Drag & drop a video"
            pick-label="select a video"
            v-slot="{ preview }"
          >
            <Player :src="preview" class="w-full h-full rounded-none" />
          </TemporaryFileInput>
        </FormControl>

        <FormControl :label="$t('Description')" :error="form.errors.description || form.errors.description_type">
          <TextEditor v-model:content="form.description" v-model:content-type="form.description_type" />
        </FormControl>

        <FormControl :label="$t('Resources')" :error="form.errors.resources" class="max-w-lg">
          <FileListInput
            class="data-[empty]:aspect-video data-[empty]:p-0"
            scope="CourseResource"
            v-model="form.resources"
            :drag-label="$t('Drag and drop files')"
            :pick-label="$t('select files')"
          />
        </FormControl>
      </div>
    </div>

    <template #footer>
      <div class="flex-1 flex flex-row justify-end items-center">
        <Button
          :icon="SaveIcon"
          :label="$t('Save')"
          @click="save"
          :processing="form.processing"
          :recently-successful="form.recentlySuccessful"
        />
      </div>
    </template>
  </CourseContentLayout>
</template>

<script setup lang="ts">
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
import { Player } from "@/Components/Player";
import { TemporaryFileInput } from "@/Components/TemporaryFileInput";
import { TextEditor } from "@/Components/TextEditor";
import type { TextContentType } from "@/Types";
import { useForm } from "@inertiajs/vue3";
import { asyncRouter } from "@stacktrace/ui";
import { trans } from "laravel-vue-i18n";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";
import { Trash2Icon, EllipsisIcon, SaveIcon } from 'lucide-vue-next'

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
    fallbackTitle: string
    description: string | null
    descriptionType: TextContentType
    video: string | null
    resources: Array<FileListItem>
  }
}>()

const form = useForm(() => ({
  title: props.lesson.title || '',
  description: props.lesson.description || '',
  description_type: props.lesson.descriptionType,
  video: null,
  remove_video: false,
  resources: props.lesson.resources.map(it => ({...it})),
}))
const save = () => {
  form.patch(route('studio.course.lessons.update', [props.id, props.chapter.id, props.lesson.id]), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(trans('Are you sure you want to delete this lesson? All resources including video will be permanently deleted.'), async () => {
  await asyncRouter.delete(route('studio.course.lessons.destroy', [props.id, props.chapter.id, props.lesson.id]))
}, { title: trans('Delete lesson'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
</script>
