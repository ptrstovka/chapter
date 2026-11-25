<template>
  <StudioLayout>
    <div class="flex flex-1 flex-col">
      <div class="flex min-h-14 flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b px-3 py-2">
        <TabsNavigation :menu="menu" />

        <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
          <Button
            v-if="isPublished && course.slug"
            as="a"
            variant="outline"
            :href="route('courses.show', course.slug)"
            target="_blank"
            rel="noopener"
          >
            {{ $t('View on Platform') }}
            <ExternalLinkIcon class="size-3.5" aria-hidden="true" />
          </Button>

          <div class="inline-flex items-center rounded-md shadow-xs" role="group" :aria-label="$t('Course actions')">
            <span class="sr-only" role="status">
              {{ publishedWithChanges ? $t('Unpublished changes') : '' }}
            </span>
            <Button
              v-if="course.isPublishing"
              variant="outline"
              class="shadow-none"
              :disabled="true"
            >
              <Spinner class="size-3.5" aria-hidden="true" />
              {{ $t('Publishing…') }}
            </Button>
            <template v-else>
              <div v-if="showPublish" class="relative focus-within:z-10" :title="publishedWithChanges ? $t('Unpublished changes') : undefined">
                <Button
                  :variant="publicationButtonVariant"
                  class="shadow-none"
                  :class="{ 'rounded-r-none': showCourseActions }"
                  :disabled="publishDisabled"
                  :processing="publishing"
                  :label="$t(publishedWithChanges ? 'Publish changes' : 'Publish')"
                  @click="publish"
                />
                <span
                  v-if="publishedWithChanges"
                  class="pointer-events-none absolute -top-0.5 -left-0.5 size-2 rounded-full bg-amber-500 ring-2 ring-background dark:bg-amber-400"
                  aria-hidden="true"
                />
              </div>
              <DropdownMenu v-if="showCourseActions">
                <DropdownMenuTrigger as-child>
                  <Button
                    :variant="publicationButtonVariant"
                    :aria-label="showPublish ? $t('Course actions') : undefined"
                    class="shadow-none focus-visible:z-10"
                    :class="{
                      'rounded-l-none px-2': showPublish,
                      'border-l border-l-positive-foreground/30': publishedWithChanges,
                      'border-l-0': showPublish && !isPublished,
                    }"
                  >
                    <template v-if="!showPublish">
                      <CheckIcon class="size-3.5" aria-hidden="true" />
                      {{ $t('Published') }}
                    </template>
                    <ChevronDownIcon class="size-3.5 opacity-70" aria-hidden="true" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem v-if="hasDraft || (course.status === 'published' && editor?.dirty)" :disabled="busy || !!stale" @select="discard">
                    {{ $t('Discard changes') }}
                  </DropdownMenuItem>
                  <DropdownMenuItem v-if="course.canBeUnpublished" :disabled="busy || !!stale || !course.canUnpublish" @select="unpublish">
                    {{ $t('Unpublish') }}
                  </DropdownMenuItem>
                  <DropdownMenuItem v-if="course.canBeDeleted" :disabled="busy || !!stale || !course.canDelete" variant="destructive" @select="destroy">
                    {{ $t(course.status === 'draft' ? 'Discard draft' : 'Delete course') }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </template>
          </div>
        </div>
      </div>

      <div v-if="stale" role="alert" class="flex flex-wrap items-center justify-between gap-2 border-b px-3 py-2 text-sm text-destructive">
        <p>{{ stale }}</p>
        <Button variant="outline" size="sm" @click="reload">{{ $t('Reload course') }}</Button>
      </div>
      <p v-else-if="publicationMessage" role="status" class="border-b px-3 py-2 text-sm text-muted-foreground">
        {{ publicationMessage }}
      </p>

      <div class="flex min-h-0 flex-1 flex-col" :inert="publishing || acting">
        <slot />
      </div>
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { useConfirmable } from '@/Components/ConfirmationDialog'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/DropdownMenu'
import { Spinner } from '@/Components/Spinner'
import { TabsNavigation } from '@/Components/Tabs'
import { StudioLayout } from '@/Layouts'
import type { AppPageProps } from '@/Types'
import { router, usePage } from '@inertiajs/vue3'
import { useNavigation } from '@stacktrace/ui'
import { trans } from 'laravel-vue-i18n'
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useVideoUploads } from '@/Components/VideoUpload/manager'
import { CheckIcon, ChevronDownIcon, ExternalLinkIcon } from 'lucide-vue-next'
import type { CourseEditorForm } from '../useCourseEditorForm'

const props = defineProps<{ editor?: CourseEditorForm }>()
const page = usePage<AppPageProps & {
  id: string
  slug: string | null
  status: 'draft' | 'publishing' | 'published' | 'unpublished' | 'publish-failure'
  isPublishing: boolean
  canBePublished: boolean
  publicationProblem: string | null
  hasUnpublishedChanges: boolean
  hasCourseDraft: boolean
  revision: number
  canPublish: boolean
  canBeUnpublished: boolean
  canUnpublish: boolean
  canDelete: boolean
  canBeDeleted: boolean
  canManageAccess: boolean
}>()

interface PublicationState {
  canBePublished: boolean
  publicationProblem: string | null
  hasUnpublishedChanges: boolean
  hasCourseDraft: boolean
}

const course = computed(() => page.props)
const uploads = useVideoUploads()
const { confirm } = useConfirmable()
const publishing = ref(false)
const acting = ref(false)
const busy = computed(() => publishing.value || acting.value || props.editor?.processing)
const stale = computed(() => page.props.errors.revision)
const media = ref<PublicationState>(page.props)
const isPublished = computed(() => course.value.status === 'published')
const publicationButtonVariant = computed(() => isPublished.value ? 'positive' : 'outline')
const publishedWithChanges = computed(() => isPublished.value && (media.value.hasUnpublishedChanges || !!props.editor?.dirty))
const hasDraft = computed(() => media.value.hasCourseDraft)
const showPublish = computed(() => !isPublished.value || publishedWithChanges.value)
const showCourseActions = computed(() => course.value.canBeUnpublished || course.value.canBeDeleted || hasDraft.value)
const pendingUpload = computed(() => uploads.hasPending(course.value.id))
const publishDisabled = computed(() => !!(busy.value || stale.value || !course.value.canPublish || pendingUpload.value
  || (!media.value.canBePublished && !props.editor?.dirty)))
const publicationMessage = computed(() => {
  if (page.props.errors.publication) return page.props.errors.publication
  if (course.value.isPublishing) return null
  if (pendingUpload.value) return trans('Wait for the video uploads to finish before publishing.')
  if (course.value.status === 'published' && !hasDraft.value) return null

  return media.value.publicationProblem ?? (course.value.status === 'publish-failure' ? trans('Publishing failed') : null)
})

watch(() => [page.props.id, page.props.revision, page.props.status, page.props.publicationProblem,
  page.props.hasUnpublishedChanges, page.props.hasCourseDraft], () => { media.value = page.props })

let timer: ReturnType<typeof setInterval> | undefined
let refreshing = false
async function refreshReadiness() {
  if (refreshing || busy.value) return
  refreshing = true
  const id = course.value.id
  try {
    const { data } = await axios.get<PublicationState & { courseStatus: string; revision: number }>(route('studio.videos', id))
    if (course.value.id === id && data.revision >= page.props.revision) {
      media.value = data
      if (course.value.isPublishing && data.courseStatus !== 'publishing') router.reload()
    }
  } finally {
    refreshing = false
  }
}

const unsubscribeUploads = uploads.subscribe(id => {
  if (id === course.value.id) void refreshReadiness().catch(() => undefined)
})
onMounted(() => { timer = setInterval(() => { void refreshReadiness().catch(() => undefined) }, 5000) })
onBeforeUnmount(() => { clearInterval(timer); unsubscribeUploads() })

const menu = useNavigation(computed(() => [
  {
    title: trans('General'),
    action: { route: 'studio.courses.show', params: [page.props.id] },
  },
  {
    title: trans('Content'),
    action: { route: 'studio.courses.content', params: [page.props.id] },
    active: [
      { route: 'studio.courses.content' },
      { route: 'studio.course.lessons*' },
      { route: 'studio.course.chapters*' },
    ],
  },
  ...(page.props.canManageAccess ? [{
    title: trans('Access'),
    action: { route: 'studio.courses.access', params: [page.props.id] },
    active: [{ route: 'studio.courses.access*' }],
  }] : []),
]))

const reload = () => router.get(route('studio.courses.show', course.value.id), {}, { replace: true })

const destroy = () => confirm(
  trans(page.props.status === 'draft' ? 'Are you sure you want to delete this draft course?' : 'Are you sure you want to delete this course? All lessons and statistics will be permanently deleted.'),
  () => {
    acting.value = true
    router.delete(route('studio.courses.destroy', page.props.id), {
      data: { revision: page.props.revision },
      onFinish: () => { acting.value = false },
    })
  },
  { title: trans('Delete course'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true },
)

const discard = () => confirm(
  trans('Discard all changes and restore the last published version?'),
  () => {
    acting.value = true
    router.delete(route('studio.courses.draft.destroy', course.value.id), {
      data: { revision: page.props.revision },
      preserveState: false,
      onSuccess: () => { void uploads.refresh().catch(() => undefined) },
      onFinish: () => { acting.value = false },
    })
  },
  { title: trans('Discard changes'), confirmLabel: trans('Discard changes'), cancelLabel: trans('Keep'), destructive: true },
)

const unpublish = () => confirm(
  trans("Are you sure you want to unpublish this course? Your audience won't be able to access this course anymore."),
  () => {
    acting.value = true
    router.post(route('studio.courses.unpublish', course.value.id), { revision: page.props.revision }, {
      preserveScroll: true,
      onFinish: () => { acting.value = false },
    })
  },
  { title: trans('Unpublish Course'), confirmLabel: trans('Unpublish'), destructive: true },
)

function publish() {
  if (publishDisabled.value) return
  if (course.value.status === 'published') {
    void submitPublication()
  } else {
    confirm(
      trans('Are you sure you want to publish this course? The course will be accessible to your audience.'),
      submitPublication,
      { title: trans('Publish Course'), confirmLabel: trans('Publish') },
    )
  }
}

async function submitPublication() {
  publishing.value = true
  try {
    if (props.editor?.dirty && !(await props.editor.save())) {
      publishing.value = false
      return
    }

    router.post(route('studio.courses.publish', course.value.id), { revision: page.props.revision }, {
      preserveScroll: true,
      onFinish: () => { publishing.value = false },
    })
  } catch {
    publishing.value = false
  }
}
</script>
