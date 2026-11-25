import type { AppPageProps } from '@/Types'
import type { FormDataType } from '@inertiajs/core'
import { type InertiaForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick } from 'vue'

export interface CourseEditorForm {
  dirty: boolean
  processing: boolean
  save: () => Promise<boolean>
}

export function useCourseEditorForm<T extends FormDataType<T>>(
  form: InertiaForm<T>,
  url: () => string,
  canEdit: () => boolean,
) {
  const page = usePage<AppPageProps & { revision: number }>()
  const save = () => {
    if (!canEdit() || form.processing || page.props.errors.revision) return Promise.resolve(false)
    if (!form.isDirty) return Promise.resolve(true)

    return new Promise<boolean>(resolve => {
      let saved = false
      form.transform(data => ({ ...data, revision: page.props.revision })).patch(url(), {
        preserveScroll: true,
        onSuccess: async () => {
          await nextTick()
          form.reset()
          saved = true
        },
        onFinish: () => resolve(saved),
      })
    })
  }

  const editor = computed<CourseEditorForm>(() => ({ dirty: form.isDirty, processing: form.processing, save }))

  return { editor, save }
}
