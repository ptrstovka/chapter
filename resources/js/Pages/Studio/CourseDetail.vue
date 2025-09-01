<template>
  <CourseLayout :title="$t('General')">
    <div class="relative h-[calc(100vh_-_8.5rem)] overflow-hidden flex flex-row">
      <div class="flex-1 flex flex-col">
        <div class="flex-1 overflow-y-auto [scrollbar-width:thin]">
          <div class="flex flex-col gap-4 p-3 md:p-4 max-w-4xl">
            <div class="grid md:grid-cols-2 gap-4">
              <FormControl :label="$t('Title')" :error="form.errors.title">
                <Input v-model="form.title" :disabled="disabled" />
              </FormControl>

              <FormControl :label="$t('URL Slug')" :error="form.errors.slug" v-if="title || slug">
                <Input v-model="form.slug" :disabled="disabled" />
              </FormControl>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <FormControl :label="$t('Category')" :error="form.errors.category">
                <FormCombobox
                  class="w-full"
                  :options="categories"
                  v-model="form.category"
                  :placeholder="$t('Select category…')"
                  :search-label="$t('Search category…')"
                  :not-found-label="$t('No category found.')"
                  :disabled="disabled"
                />
              </FormControl>
            </div>

            <FormControl :label="$t('Description')" :error="form.errors.description || form.errors.description_type">
              <TextEditor
                v-model:content="form.description"
                v-model:content-type="form.description_type"
                :disabled="disabled"
              />
            </FormControl>

            <div class="grid lg:grid-cols-2 gap-4">
              <FormControl :label="$t('Trailer')">
                <TemporaryFileInput
                  class="aspect-video"
                  scope="CourseTrailerVideo"
                  :source="trailer"
                  v-model:remove="form.remove_trailer"
                  v-model:file="form.trailer"
                  drag-label="Drag & drop a video"
                  pick-label="select a video"
                  :disabled="disabled"
                  v-slot="{ preview }"
                >
                  <Player :src="preview" class="w-full h-full rounded-none" />
                </TemporaryFileInput>
              </FormControl>

              <FormControl :label="$t('Cover Image')">
                <TemporaryFileInput
                  class="aspect-video"
                  scope="CourseCoverImage"
                  :source="coverImage"
                  v-model:remove="form.remove_cover_image"
                  v-model:file="form.cover_image"
                  drag-label="Drag & drop an image"
                  pick-label="select an image"
                  :disabled="disabled"
                  v-slot="{ preview }"
                >
                  <img class="w-full h-full object-contain object-center" :src="preview" alt="">
                </TemporaryFileInput>
              </FormControl>
            </div>
          </div>
        </div>

        <div class="h-12 w-full flex px-3 justify-end items-center border-t bg-secondary/30">
          <Button
            :icon="SaveIcon"
            :processing="form.processing"
            :recently-successful="form.recentlySuccessful"
            :label="$t('Save')"
            @click="save"
            :disabled="disabled"
          />
        </div>
      </div>
    </div>
  </CourseLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { FormCombobox, FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { TextEditor } from "@/Components/TextEditor";
import type { TextContentType } from "@/Types";
import { useForm } from "@inertiajs/vue3";
import type { SelectOption } from "@stacktrace/ui";
import { SaveIcon } from 'lucide-vue-next'
import { TemporaryFileInput } from '@/Components/TemporaryFileInput'
import { Player } from '@/Components/Player'
import { computed } from "vue";
import CourseLayout from "./Layouts/CourseLayout.vue"

const props = defineProps<{
  id: string
  title: string | null
  slug: string | null
  description: string | null
  descriptionType: TextContentType
  coverImage: string | null
  trailer: string | null
  categories: Array<SelectOption<number>>
  category: number | null
  isEditable: boolean
}>()

const disabled = computed(() => !props.isEditable)

const form = useForm(() => ({
  title: props.title || '',
  slug: props.slug || '',
  description: props.description || '',
  description_type: props.descriptionType,
  cover_image: null,
  remove_cover_image: false,
  trailer: null,
  remove_trailer: false,
  category: props.category,
}))

const save = () => {
  form.patch(route('studio.courses.update', props.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}
</script>
