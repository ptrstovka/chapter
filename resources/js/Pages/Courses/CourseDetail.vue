<template>
  <Head :title="title" />

  <AuthenticatedLayout class="bg-background">
    <div class="py-8">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold leading-tight">{{ title }}</h1>

        <div class="flex flex-row gap-8 mt-6">
          <div class="flex flex-col w-full gap-6">
            <Player
              v-if="trailer"
              :src="trailer.url"
              :poster="trailer.posterImageUrl"
            />

            <Tabs :default-value="description ? 'description' : 'lessons'">
              <TabsList>
                <TabsTrigger v-if="description" value="description">{{ $t('About Course') }}</TabsTrigger>
                <TabsTrigger value="lessons">{{ $t('Lessons') }}</TabsTrigger>
              </TabsList>
              <TabsContent v-if="description" value="description">
                <div class="editor-content" v-html="description"></div>
              </TabsContent>
              <TabsContent value="lessons">
                <Accordion type="multiple" collapsible>
                  <AccordionItem :value="chapter.id" v-for="chapter in chapters">
                    <AccordionTrigger>{{ chapter.title }}</AccordionTrigger>
                    <AccordionContent>
                      <ul class="flex flex-col gap-2">
                        <li v-for="lesson in chapter.lessons" class="flex flex-row justify-between p-4 text-sm border rounded-md bg-background">
                          <p class="font-medium">{{ lesson.title }}</p>
                          <p class="font-mono tabular-nums text-muted-foreground" v-if="lesson.duration">{{ lesson.duration }}</p>
                        </li>
                      </ul>
                    </AccordionContent>
                  </AccordionItem>
                </Accordion>
              </TabsContent>
            </Tabs>
          </div>

          <div class="flex-shrink-0 w-96">
            <Card class="w-full">
              <CardContent class="flex flex-col items-center p-6">
                <Avatar size="base" shape="square">
                  <AvatarImage v-if="author.avatarUrl" :src="author.avatarUrl" />
                  <AvatarFallback>
                    <ContactIcon class="w-6 h-6" />
                  </AvatarFallback>
                </Avatar>
                <h3 class="mt-4 text-lg font-semibold">{{ author.name }}</h3>
                <p v-if="author.bio" class="mt-1 text-sm text-center text-muted-foreground">{{ author.bio }}</p>

                <div class="mt-4" v-if="enrollment">
                  <Badge variant="positive" v-if="enrollment.isCompleted">{{ $t('Course Completed!') }}</Badge>
                  <p class="text-sm text-muted-foreground" v-else>{{ $t(':value% completed', { value: `${enrollment.progress}` }) }}</p>
                </div>

                <div class="flex flex-col w-full gap-2 mt-4">
                  <LinkButton v-if="enrollment" :href="route('courses.begin', slug)">
                    {{ enrollment.isCompleted ? $t('Browse Lessons') : (enrollment.completedLessons === 0 ? $t('Start Learning') : $t('Continue Learning')) }}
                  </LinkButton>
                  <Button v-else :processing="enrollForm.processing" @click="enroll">{{ $t('Enroll') }}</Button>
                </div>

                <Button @click="resetProgress" v-if="enrollment && enrollment.completedLessons > 0" class="w-full mt-3" variant="ghost">{{ $t('Start Over') }}</Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { useConfirmable } from '@/Components/ConfirmationDialog'
import { AuthenticatedLayout } from '@/Layouts'
import type { VideoSource } from '@/Types'
import { asyncRouter } from '@/Utils'
import { Head, useForm } from '@inertiajs/vue3'
import { Card, CardContent } from '@/Components/Card'
import { Player } from '@/Components/Player'
import { trans } from 'laravel-vue-i18n'
import { ContactIcon } from 'lucide-vue-next'
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/Avatar'
import { Button, LinkButton } from '@/Components/Button'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/Tabs'
import { Accordion, AccordionTrigger, AccordionContent, AccordionItem } from '@/Components/Accordion'
import { Badge } from '@/Components/Badge'

const props = defineProps<{
  id: string
  slug: string
  title: string
  trailer: VideoSource | null
  description: string | null
  author: {
    name: string
    avatarUrl: string | null
    bio: string | null
  }
  enrollment: {
    isCompleted: boolean
    progress: number
    completedLessons: number
  } | null
  chapters: Array<{
    id: string
    title: string | null
    lessons: Array<{
      id: string
      title: string
      duration: string | null
    }>
  }>
}>()

const enrollForm = useForm({})
const enroll = () => {
  if (enrollForm.processing) {
    return
  }

  enrollForm.post(route('courses.enroll', props.id))
}

const { confirm } = useConfirmable()

const resetProgress = () => confirm(trans('Are you sure you want to restart this course? Your current progress will be reset, and you’ll begin from the very first lesson. This action cannot be undone.'), async () => {
  await asyncRouter.post(route('courses.reset-progress', props.slug))
}, {
  title: trans('Start Over?'),
  destructive: true,
  cancelLabel: trans('Keep Progress'),
  confirmLabel: trans('Start Over'),
})
</script>
