<template>
  <Head :title="category || 'All courses'" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-row gap-8">
          <div class="w-72">
            <h2 class="font-semibold text-xl leading-tight">Categories</h2>

            <ul class="mt-4 -ml-4 flex flex-col gap-1">
              <li v-for="category in categories">
                <Button
                  @click="filter.category = category.value"
                  :variant="filter.category == category.value ? 'default' : 'ghost'"
                  class="w-full justify-start"
                  plain
                >{{ category.title }}</Button>
              </li>
              <li>
                <Button @click="filter.category = null" variant="ghost" class="w-full justify-start" plain>All courses</Button>
              </li>
            </ul>
          </div>

          <div class="w-full">
            <h2 class="font-semibold text-xl leading-tight">{{ category || 'All courses' }}</h2>

            <div class="grid grid-cols-3 mt-4 w-full">
              <Link v-for="course in courses.data" :href="course.url" class="border w-full bg-background rounded-md shadow overflow-hidden">
                <div class="h-44">
                  <img class="w-full h-full object-cover" v-if="course.coverImageUrl" :src="course.coverImageUrl" :alt="`Cover image of ${course.title} course`">
                  <div v-else class="w-full h-full bg-stone-50 flex items-center justify-center">
                    <ImageOffIcon class="w-5 h-5 text-stone-400" />
                  </div>
                </div>
                <div class="border-t px-2 py-3">
                  <p class="font-semibold">{{ course.title }}</p>

                  <div class="flex flex-row justify-between mt-2">
                    <p class="text-xs font-medium text-muted-foreground inline-flex items-center gap-1">
                      <ContactIcon class="w-4 h-4" />
                      {{ course.author.name }}
                    </p>
                    <p v-if="course.duration" class="text-xs font-medium text-muted-foreground inline-flex items-center gap-1">
                      <TimerIcon class="w-4 h-4" />
                      {{ course.duration }}
                    </p>
                  </div>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { useFilter } from '@/Composables'
import { Head, Link } from '@inertiajs/vue3'
import { AuthenticatedLayout } from '@/Layouts'
import { Button } from '@/Components/Button'
import type { Paginator } from '@/Types'
import { ContactIcon, TimerIcon, ImageOffIcon } from 'lucide-vue-next'

defineProps<{
  category: string | null
  categories: Array<{
    value: string
    title: string
  }>
  courses: Paginator<{
    title: string
    url: string
    coverImageUrl: string | null
    duration: string | null
    author: {
      name: string
    }
  }>
}>()

const filter = useFilter(() => ({
  category: null as string | null,
}))
</script>
