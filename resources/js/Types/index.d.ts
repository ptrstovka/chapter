export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth: {
    user: User;
  };
};

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
