<template>
  <CourseLayout :title="$t('General')">
    <div class="flex flex-col gap-4">
      <div class="grid md:grid-cols-2 gap-4">
        <FormControl :label="$t('Title')" :error="form.errors.title">
          <Input v-model="form.title" />
        </FormControl>

        <FormControl :label="$t('URL Slug')" :error="form.errors.slug" v-if="title || slug">
          <Input v-model="form.slug" />
        </FormControl>
      </div>

      <FormControl :label="$t('Description')" :error="form.errors.description || form.errors.description_type">
        <TextEditor
          v-model:content="form.description"
          v-model:content-type="form.description_type"
        />
      </FormControl>

      <Button
        class="self-start"
        :icon="SaveIcon"
        :processing="form.processing"
        :recently-successful="form.recentlySuccessful"
        :label="$t('Save')"
        @click="save"
      />
    </div>
  </CourseLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { FormControl } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { TextEditor } from "@/Components/TextEditor";
import type { TextContentType } from "@/Types";
import { useForm } from "@inertiajs/vue3";
import { SaveIcon } from 'lucide-vue-next'
import CourseLayout from "./Layouts/CourseLayout.vue"

const props = defineProps<{
  id: string
  title: string | null
  slug: string | null
  description: string | null
  descriptionType: TextContentType
}>()

const form = useForm(() => ({
  title: props.title || '',
  slug: props.slug || '',
  description: props.description || '',
  description_type: props.descriptionType,
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
