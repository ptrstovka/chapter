export { default as CourseCard } from './CourseCard.vue'

export interface Course {
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
}
