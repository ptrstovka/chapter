import { useFilter } from "@stacktrace/ui";

export function useCourseFilter() {
  const filter = useFilter(() => ({
    category: null as string | null,
    sort: 'latest',
    hideCompleted: false,
    onlyFavorite: false,
  }))

  return filter
}

export type CourseFilter = ReturnType<typeof useCourseFilter>
