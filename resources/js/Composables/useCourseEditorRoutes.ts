import type { AppPageProps } from '@/Types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export type CourseEditorPrefix = 'studio' | 'admin.course-editor'

export function useCourseEditorRoutes() {
  const page = usePage<AppPageProps & { courseEditorPrefix: CourseEditorPrefix }>()
  const courseEditorPrefix = computed(() => page.props.courseEditorPrefix)

  return {
    courseEditorPrefix,
    courseRouteName: (name: string) => `${courseEditorPrefix.value}.${name}`,
  }
}
