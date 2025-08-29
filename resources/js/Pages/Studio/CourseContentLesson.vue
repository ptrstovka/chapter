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
        <FormControl :label="$t('Title')" :error="form.errors.title">
          <Input v-model="form.title" :placeholder="lesson.fallbackTitle" />
        </FormControl>

        <div>
          <Button
            :icon="SaveIcon"
            :label="$t('Save')"
            @click="save"
            :processing="form.processing"
          />
        </div>
      </div>
    </div>
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
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
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
  }
}>()

const form = useForm(() => ({
  title: props.lesson.title || '',
}))
const save = () => {
  form.patch(route('studio.course.lessons.update', [props.id, props.chapter.id, props.lesson.id]), {
    preserveScroll: true,
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(trans('Are you sure you want to delete this lesson? All resources including video will be permanently deleted.'), async () => {
  await asyncRouter.delete(route('studio.course.lessons.destroy', [props.id, props.chapter.id, props.lesson.id]))
}, { title: trans('Delete lesson'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
</script>
