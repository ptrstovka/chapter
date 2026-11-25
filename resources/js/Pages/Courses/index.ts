export interface Resource {
  name: string
  url: string
  size: string
}

export interface CoursePreview {
  version: 'draft' | 'published'
  versionLabel: string
  courseUrl: string
  studioUrl: string
  returnLabel: string
}

export interface Lesson {
  slugId: string
  title: string
  no: number
  isCurrent: boolean
  duration: string | null
  url: string
  isCompleted: boolean
}

export interface Chapter {
  no: number
  title: string
  lessons: Array<Lesson>
}
