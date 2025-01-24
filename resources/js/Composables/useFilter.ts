import { reactive, watch } from "vue"
import isEqual from "lodash/isEqual"
import cloneDeep from "lodash/cloneDeep"
import type { ParsedQuery } from 'query-string'
import { router } from "@inertiajs/vue3"
import { useDebounceFn } from "@vueuse/core"
import { parseQuery, urlWithQuery } from "@/Utils"

const isEmptyArray = (value: any) => Array.isArray(value) && value.length === 0

const isSame = (first: any, second: any) => {
  // Empty string, undefined, false, null and empty array are considered to be same.
  if (second === '' || second === undefined || second === null || isEmptyArray(second) || second === false) {
    return first === '' || first === undefined || first === null || isEmptyArray(first) || first === false;
  }

  return isEqual(first, second)
}

export type FilterData = object
export interface FilterProps<TFilter extends FilterData> {
  data(): TFilter
  reset(): void
  applied: boolean
}
export type Filter<TFilter extends FilterData> = TFilter & FilterProps<TFilter>

export interface FilterOptions {
  onSuccess: VoidFunction
}

export function useFilter<TFilter extends FilterData>(state: TFilter | (() => TFilter), options?: Partial<FilterOptions>): Filter<TFilter> {
  const defaults = typeof state === 'object' ? cloneDeep(state) : cloneDeep(state())

  const getInitialValue = () => {
    const query = parseQuery() as TFilter

    return (Object.keys(defaults) as Array<keyof TFilter>).reduce((carry, key) => {
      if (isSame(query[key], defaults[key]) || query[key] === undefined) {
        carry[key] = defaults[key]
      } else {
        carry[key] = query[key]
      }
      return carry
    }, {} as Partial<TFilter>) as TFilter
  }

  const isApplied = (value: TFilter) => (Object.keys(defaults) as Array<keyof TFilter>).some(key => ! isSame(value[key], defaults[key]))

  const initialValue = getInitialValue()

  const filter = reactive({
    ...initialValue,
    applied: isApplied(initialValue),
    data() {
      return (Object.keys(defaults) as Array<keyof TFilter>).reduce((carry, key) => {
        carry[key] = (this as TFilter)[key]
        return carry
      }, {} as Partial<TFilter>) as TFilter
    },
    reset() {
      (Object.keys(defaults) as Array<keyof TFilter>).forEach(key => {
        (this as TFilter)[key] = defaults[key]
      })
      this.applied = false
    },
  })

  const toUrl = (data: TFilter) => {
    const query = parseQuery() as ParsedQuery & TFilter;

    (Object.keys(data) as Array<keyof TFilter>).forEach(key => {
      const currentValue = data[key]
      const defaultValue = defaults[key]

      if (isSame(currentValue, defaultValue) || !currentValue) {
        delete query[key]
      } else {
        query[key] = currentValue as any
      }
    });

    delete query['page']
    delete query['cursor']

    return urlWithQuery(query)
  }

  let cancelToken: { cancel: () => void } | null = null

  const visit = useDebounceFn((filterUrl: string) => {
    if (cancelToken) {
      cancelToken.cancel()
      cancelToken = null
    }

    router.visit(filterUrl, {
      preserveState: true,
      preserveScroll: true,
      onCancelToken: (token) => {
        cancelToken = token
      },
      onSuccess: () => {
        filter.applied = isApplied(filter.data())

        if (options?.onSuccess) {
          options.onSuccess()
        }
      }
    })
  }, 50)

  watch(filter, () => {
    const currentUrl = window.location.href
    const filterUrl = toUrl(filter.data())

    if (currentUrl != filterUrl) {
      filter.applied = isApplied(filter.data())

      visit(filterUrl)
    }
  })

  return filter as Filter<TFilter>
}
