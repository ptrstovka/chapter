<template>
  <Head :title="category || $t('All Courses')" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
      <div class="flex flex-row gap-8">
        <DesktopFilter class="w-72 hidden lg:block" :categories="categories" :filter="filter" />

        <div class="w-full">
          <div class="flex flex-row justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight">{{ category || $t('All Courses') }}</h2>

            <CourseSorting class="hidden lg:block" v-if="courses.total > 0" :options="sortOptions" :filter="filter" />
          </div>

          <div class="mt-4 lg:hidden flex flex-row gap-2">
            <Button @click="mobileFilter.activate" variant="outline">
              <FilterIcon class="text-muted-foreground" /> {{ $t('Filter') }}
            </Button>

            <CourseSorting v-if="courses.total > 0" :options="sortOptions" :filter="filter" />
          </div>

          <EmptyState
            v-if="courses.total === 0"
            :title="$t('No Courses Available')"
            :message="$t('There are no video courses available right now. Please check back later!')"
            class="mt-12"
          />

          <div v-if="courses.data.length > 0" class="grid sm:grid-cols-2 lg:grid-cols-3 my-4 lg:my-6 gap-4 w-full">
            <CourseCard v-for="course in courses.data" :course="course" />
          </div>

          <div v-if="courses.total > 0" class="flex flex-row justify-center lg:justify-end w-full">
            <SimplePagination :paginator="courses" />
          </div>
        </div>
      </div>
    </div>

    <Drawer :control="mobileFilter">
      <DrawerContent>
        <div class="overflow-y-auto">
          <DesktopFilter
            :categories="categories"
            :filter="filter"
            class="p-4"
            @click="mobileFilter.deactivate"
          />

          <DrawerFooter>
            <Button @click="mobileFilter.deactivate">{{ $t('Close') }}</Button>
          </DrawerFooter>
        </div>
      </DrawerContent>
    </Drawer>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { Drawer, DrawerContent, DrawerFooter } from "@/Components/Drawer";
import { EmptyState } from '@/Components/EmptyState'
import { useCourseFilter } from "./Composables/useFilter.ts";
import CourseSorting from "./Components/CourseSorting.vue";
import DesktopFilter from "./Components/DesktopFilter.vue";
import { Head } from '@inertiajs/vue3'
import { AuthenticatedLayout } from '@/Layouts'
import type { Paginator } from '@/Types'
import { type SelectOption, useToggle } from "@stacktrace/ui";
import { trans } from 'laravel-vue-i18n'
import { computed } from "vue";
import { SimplePagination } from '@/Components/Pagination'
import type { Course } from '@/Components/Course'
import { CourseCard } from '@/Components/Course'
import { FilterIcon } from 'lucide-vue-next'

defineProps<{
  category: string | null
  categories: Array<SelectOption>
  courses: Paginator<Course>
}>()

const mobileFilter = useToggle()

const sortOptions = computed<Array<SelectOption>>(() => [
  { value: 'latest', label: trans('Latest') },
  { value: 'popular', label: trans('Popular') },
  { value: 'title-asc', label: trans('A-Z') },
  { value: 'title-desc', label: trans('Z-A') },
])

const filter = useCourseFilter()
</script>
