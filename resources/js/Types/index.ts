import type { BackendToast } from "@/Components/Sonner/useBackendSonner.ts";
import type { Config } from 'ziggy-js'

export interface User {
  id: number
  name: string
  email: string
  emailVerifiedAt: string
  can: {
    accessStudio: boolean
    viewAdmin: boolean
  }
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  appName: string
  auth: {
    user: User
  }
  locale: string
  ziggy: Config & { location: string }
  toasts: Array<BackendToast>
}

export interface Paginator<T> {
  currentPage: number
  data: Array<T>
  firstPageUrl: string
  from: number
  lastPage: number
  lastPageUrl: string
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
  nextPageUrl: string | null
  path: string
  perPage: number
  prevPageUrl: string | null
  to: number
  total: number
}

export interface VideoSource {
  url: string
  posterImageUrl: string | null
}

export type TextContentType = 'html' | 'markdown'
