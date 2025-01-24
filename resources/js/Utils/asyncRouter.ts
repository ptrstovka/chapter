import { router } from "@inertiajs/vue3";
import type { VisitOptions, GlobalEventParameters, RequestPayload } from "@inertiajs/core";

export interface AsyncRouter {
  visit: (href: string | URL, options?: Exclude<VisitOptions, 'onSuccess' | 'onError' | 'onCancel'>) => Promise<GlobalEventParameters<'success'>>
  post: (url: URL | string, data?: RequestPayload, options?: Exclude<VisitOptions, 'onSuccess' | 'onError' | 'onCancel' | 'method' | 'data'>) => Promise<GlobalEventParameters<'success'>>
  patch: (url: URL | string, data?: RequestPayload, options?: Exclude<VisitOptions, 'onSuccess' | 'onError' | 'onCancel' | 'method' | 'data'>) => Promise<GlobalEventParameters<'success'>>
  delete: (url: URL | string, options?: Exclude<VisitOptions, 'onSuccess' | 'onError' | 'onCancel' | 'method' | 'data'>) => Promise<GlobalEventParameters<'success'>>
}

export const asyncRouter: AsyncRouter = {
  visit(href: string | URL, options: VisitOptions | undefined): Promise<GlobalEventParameters<'success'>> {
    return new Promise((resolve, reject) => {
      router.visit(href, {
        ...options,
        onSuccess: (...args) => resolve(args),
        onError: reject,
        onCancel: reject,
      })
    })
  },

  patch(url: URL | string, data: RequestPayload | undefined, options: Exclude<VisitOptions, "onSuccess" | "onError" | "onCancel" | "method" | "data"> | undefined): Promise<GlobalEventParameters<"success">> {
    return this.visit(url, {...options, data, method: 'patch' })
  },

  post(url: URL | string, data: RequestPayload | undefined, options: Exclude<VisitOptions, "onSuccess" | "onError" | "onCancel" | "method" | "data"> | undefined): Promise<GlobalEventParameters<"success">> {
    return this.visit(url, {...options, data, method: 'post' })
  },

  delete(url: URL | string, options: Exclude<VisitOptions, "onSuccess" | "onError" | "onCancel" | "method" | "data"> | undefined): Promise<GlobalEventParameters<"success">> {
    return this.visit(url, {...options, method: 'delete' })
  }
}
