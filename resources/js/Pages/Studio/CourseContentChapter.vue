<template>
  <CourseContentLayout>
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
          :label="$t('Save')"
          @click="save"
          :processing="form.processing"
          :recently-successful="form.recentlySuccessful"
          :disabled="!chapter.isEditable"
        />
      </div>
    </template>
  </CourseContentLayout>
</template>

<script setup lang="ts">
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
import { useForm } from "@inertiajs/vue3";
import { asyncRouter } from "@stacktrace/ui";
import { trans } from "laravel-vue-i18n";
import { EllipsisIcon, SaveIcon, Trash2Icon } from "lucide-vue-next";
import CourseContentLayout from "./Layouts/CourseContentLayout.vue";

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
const save = () => {
  if (!props.chapter.isEditable) {
    return
  }

  form.patch(route('studio.course.chapters.update', [props.id, props.chapter.id]), {
    preserveScroll: true,
  })
}

useSaveShortcut(() => save())

const { confirm } = useConfirmable()
const destroy = () => confirm(trans('Are you sure you want to delete this chapter? All lessons within the chapter including their resources and videos will be permanently deleted.'), async () => {
  await asyncRouter.delete(route('studio.course.chapters.destroy', [props.id, props.chapter.id]))
}, { title: trans('Delete Chapter'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true })
</script>
