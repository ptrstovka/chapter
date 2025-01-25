<template>
  <Head :title="title" />

  <AuthenticatedLayout>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="font-semibold text-2xl leading-tight">{{ title }}</h1>

        <div class="flex flex-row gap-8 mt-6">
          <div class="w-full flex flex-col gap-6">
            <Player
              v-if="trailer"
              :src="trailer.url"
              :poster="trailer.posterImageUrl"
            />

            <Tabs :default-value="description ? 'description' : 'lessons'">
              <TabsList>
                <TabsTrigger v-if="description" value="description">About Course</TabsTrigger>
                <TabsTrigger value="lessons">Lessons</TabsTrigger>
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
                        <li v-for="lesson in chapter.lessons" class="flex flex-row justify-between bg-background rounded-md border p-4 text-sm">
                          <p class="font-medium">{{ lesson.title }}</p>
                          <p class="tabular-nums text-muted-foreground font-mono" v-if="lesson.duration">{{ lesson.duration }}</p>
                        </li>
                      </ul>
                    </AccordionContent>
                  </AccordionItem>
                </Accordion>
              </TabsContent>
            </Tabs>
          </div>

          <div class="w-96 flex-shrink-0">
            <Card class="w-full">
              <CardContent class="flex flex-col items-center p-6">
                <Avatar size="base" shape="square">
                  <AvatarImage v-if="author.avatarUrl" :src="author.avatarUrl" />
                  <AvatarFallback>
                    <ContactIcon class="w-6 h-6" />
                  </AvatarFallback>
                </Avatar>
                <h3 class="text-lg font-semibold mt-4">{{ author.name }}</h3>
                <p v-if="author.bio" class="text-center text-muted-foreground text-sm mt-1">{{ author.bio }}</p>

                <div class="mt-4" v-if="enrollment">
                  <Badge variant="positive" v-if="enrollment.isCompleted">Course Completed!</Badge>
                  <p class="text-sm text-muted-foreground" v-else>{{ enrollment.progress }}% completed</p>
                </div>

                <div class="mt-4 flex flex-col w-full gap-2">
                  <LinkButton v-if="enrollment" :href="route('courses.begin', slug)">
                    {{ enrollment.isCompleted ? 'Browse Lessons' : (enrollment.completedLessons === 0 ? 'Start Learning' : 'Continue Learning') }}
                  </LinkButton>
                  <Button v-else :processing="enrollForm.processing" @click="enroll">Enroll</Button>
                </div>

                <Button @click="resetProgress" v-if="enrollment && enrollment.completedLessons > 0" class="w-full mt-3" variant="ghost">Start Over</Button>
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

const resetProgress = () => confirm('Are you sure you want to restart this course? Your current progress will be reset, and you’ll begin from the very first lesson. This action cannot be undone.', async () => {
  await asyncRouter.post(route('courses.reset-progress', props.slug))
}, {
  title: 'Start Over?',
  destructive: true,
  cancelLabel: 'Keep Progress',
  confirmLabel: 'Start Over',
})
</script>
