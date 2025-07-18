export { default as CourseCard } from './CourseCard.vue'

export interface Course {
  slug: string
  title: string
  url: string
  coverImageUrl: string | null
  duration: string | null
  author: {
    name: string
  }
  enrollment: {
    isCompleted: boolean
    progress: number
  } | null
  isFavorite: boolean
}
