<template>
  <StudioLayout :title="$t('Courses')">
    <DataTable
      :table="courses"
      :empty-table-message="$t('No courses')"
      :empty-table-description="$t('Get started by creating your first course.')"
    >
      <template #empty-table>
        <Button @click="createCourse" :processing="createCourseForm.processing" :icon="PlusIcon" :label="$t('New Course')" class="mt-4" />
      </template>

      <template #actions>
        <Button @click="createCourse" :processing="createCourseForm.processing" :icon="PlusIcon" :label="$t('New Course')" />
      </template>
    </DataTable>
  </StudioLayout>
</template>

<script setup lang="ts">
import { StudioLayout } from '@/Layouts'
import { Button } from '@/Components/Button'
import { useForm } from "@inertiajs/vue3";
import { DataTable, type DataTableValue } from '@/Components/DataTable'
import { PlusIcon } from 'lucide-vue-next'

defineProps<{
  courses: DataTableValue
}>()

const createCourseForm = useForm({})
const createCourse = () => {
  createCourseForm.post(route('studio.courses.store'))
}
</script>
