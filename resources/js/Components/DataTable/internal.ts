import { useSelectableRows } from '@/Components/Table'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { useFilter } from '@stacktrace/ui'
import { computed, type ComputedRef, inject, provide, toRaw } from 'vue'

export interface BaseAction {
  name: string
  label: string | null
  canRun: boolean
  isBulk: boolean
  icon: { src: string } | null
  isInline: boolean
}

export interface EventAction extends BaseAction {
  type: 'Event'
  event: string
}

export interface LinkAction extends BaseAction {
  type: 'Link'
  url: string
  isExternal: boolean
}

export interface ExecutableAction extends BaseAction {
  type: 'Executable'
  confirmable: boolean
  title: string | null
  description: string | null
  cancelLabel: string
  confirmLabel: string
  action: string
  isDestructive: boolean
  args: string
}

export type Action = EventAction | LinkAction | ExecutableAction

export interface Resource<K = string | number, V = object> {
  key: K
  value: V | null
}

export interface DataTableResourceActionsValue<ResourceKey = string | number, ResourceValue = object> {
  actions: Array<Action>
  resource: Resource<ResourceKey, ResourceValue>
}

export type TextStyleProperty = 'fontWeight' | 'fontFamily' | 'whitespace'
  | 'fontVariantNumeric' | 'textDecorationLine' | 'textAlign' | 'fontStyle' | 'color'

export type TextStyle = Record<TextStyleProperty | string, string | null>

export interface Cell {
  column: string
  component: string
  props: Record<string, any>
  width: string | null
  minWidth: string | null
  maxWidth: string | null
  style: TextStyle
  link: {
    url: string
    isExternal: boolean
  } | null
}

export interface Row<ResourceKey = string | number, ResourceValue = object> {
  key: ResourceKey
  cells: Array<Cell>
  actions: Array<Action>
  resource: ResourceValue | null
  highlightAs: string | null
}

export interface CursorPagination {
  prevPageUrl: string | null
  nextPageUrl: string | null
}

export interface Pagination {
  currentPage: number
  lastPage: number
  total: number
  prevPageUrl: string | null
  nextPageUrl: string | null
  firstPageUrl: string | null
  lastPageUrl: string | null
}

export interface Filter {
  defaultValue: Record<string, any>
  widgets: Array<{
    component: string
    props: Record<string, any>
  }>
}

export interface DataTableValue<ResourceValue = object, ResourceKey = string | number> {
  headings: Array<{
    id: string
    name: string
    width: string | null
    minWidth: string | null
    maxWidth: string | null
    style: TextStyle
    sortableAs: string | null
  }>
  rows: Array<Row<ResourceKey, ResourceValue>>
  footerCells: Array<Cell | null>
  perPageOptions: Array<number>
  perPage: number
  defaultPerPage: number
  pagination: Pagination | null
  cursorPagination: CursorPagination | null
  isSearchable: boolean
  filter: Filter | null
  isEmpty: boolean
}

export const createContext = (table: ComputedRef<DataTableValue>) => {
  const rows = computed(() => table.value.rows)
  const headings = computed(() => table.value.headings)

  const shouldShowCheckboxForRow = (row: Row) => row.actions.some(it => it.isBulk && it.canRun)
  const selectableRows = useSelectableRows(
    computed(() => rows.value.map(it => it.key)),
    computed(() => rows.value.filter(row => !shouldShowCheckboxForRow(row)).map(it => it.key))
  )
  const somethingSelected = computed(() => selectableRows.somethingSelected.value)
  const selectedRows = computed(() => table.value.rows.filter(row => selectableRows.selection.value.includes(row.key)))

  // Bulk actions
  // Determine whether some row with actions is in the table.
  const hasRowActions = computed(() => table.value.rows.some(it => it.actions.filter(it => it.canRun).length > 0))
  // Determine whether some row has bulk actions.
  const hasBulkActions = computed(() => table.value.rows.some(it => it.actions.filter(it => it.canRun && it.isBulk).length > 0))
  const showBulkActions = computed(() => somethingSelected.value)
  const bulkActions = computed<Array<Action>>(() => {
    const actions: Record<string, Action> = {}

    selectedRows.value.flatMap(it => it.actions.filter(action => action.canRun && action.isBulk)).forEach(action => {
      if (!actions.hasOwnProperty(action.name)) {
        actions[action.name] = action
      }
    })

    return Object.keys(actions).map(it => actions[it])
  })

  // Filter
  const page = usePage()
  const searchFilter = useFilter(() => ({
    search: '',
  }))
  const clearSearch = () => {
    router.visit(page.url.split('?')[0])
  }
  const filter = useFilter(() => table.value.filter?.defaultValue || {})
  const sortFilter = useFilter(() => ({
    sort_by: null,
    sort_direction: null,
  }))
  const paginationFilter = useFilter(() => ({
    limit: table.value.defaultPerPage,
  }))
  const setPerPage = (limit: number) => {
    paginationFilter.limit = limit
  }
  const hasPerPageSettings = computed(() => (table.value.pagination || table.value.cursorPagination) && table.value.perPageOptions.length > 0)

  const isSearchable = computed(() => table.value.isSearchable)

  return {
    table,
    rows,
    headings,

    // Filter
    clearSearch,
    searchFilter,
    filter,
    sortFilter,
    paginationFilter,
    setPerPage,
    hasPerPageSettings,
    isSearchable,

    // Bulk selection
    hasRowActions,
    hasBulkActions,
    showBulkActions,

    // Selectable rows
    selectableRows,
    selectedRows,
    shouldShowCheckboxForRow,
    somethingSelected,
    bulkActions,
  }
}

export type Context = ReturnType<typeof createContext>

export const provideContext = (context: Context) => {
  provide('DataTableContext', context)
}

export const injectContext = () => inject<Context>('DataTableContext')!

interface ActionRunnerOptions {
  onSuccess: () => void
  onError: () => void
  onFinish: () => void
}

export function useActionRunner<ResourceKey = string | number>() {
  const form = useForm({})

  const run = (action: ExecutableAction, selection: Array<ResourceKey>, options?: Partial<ActionRunnerOptions>) => {
    form.transform(() => ({
      selection: toRaw(selection),
      action: action.action,
      args: action.args,
    })).post(route('ui.data-table-action'), {
      preserveScroll: true,
      onSuccess: () => {
        const callback = options?.onSuccess
        if (callback) {
          callback()
        }
      },
      onError: () => {
        const callback = options?.onError
        if (callback) {
          callback()
        }
      },
      onFinish: () => {
        const callback = options?.onFinish
        if (callback) {
          callback()
        }
      }
    })
  }

  return {
    isRunning: computed(() => form.processing),
    run,
  }
}

export function configureStyle<T extends Record<string, string | null>>(
  style: T,
  mapping: Record<keyof T, Record<string, string>>,
): string {
  const tokens: Array<string> = []

  Object.keys(style).forEach(key => {
    const styleValue = style[key]
    const styleConfig = mapping[key]

    if (styleValue && styleConfig[styleValue]) {
      tokens.push(styleConfig[styleValue])
    }
  })

  return tokens.join(' ')
}
