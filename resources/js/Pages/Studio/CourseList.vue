<template>
  <StudioLayout :title="$t('Courses')">
    <div class="p-4">
      <Button @click="createCourse" :processing="createCourseForm.processing">{{ $t('New Course') }}</Button>
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { StudioLayout } from '@/Layouts'
import { Button } from '@/Components/Button'
import { useForm } from "@inertiajs/vue3";

const createCourseForm = useForm({})
const createCourse = () => {
  createCourseForm.post(route('studio.courses.store'))
}
</script>
