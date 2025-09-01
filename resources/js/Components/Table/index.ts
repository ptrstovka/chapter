import { computed, type ComputedRef, type Ref, ref, watch } from "vue";

export { default as Table } from './Table.vue'
export { default as TableBody } from './TableBody.vue'
export { default as TableCaption } from './TableCaption.vue'
export { default as TableCell } from './TableCell.vue'
export { default as TableEmpty } from './TableEmpty.vue'
export { default as TableFooter } from './TableFooter.vue'
export { default as TableHead } from './TableHead.vue'
export { default as TableHeader } from './TableHeader.vue'
export { default as TableRow } from './TableRow.vue'

export { default as BulkSelect } from './BulkSelect.vue'
export { default as RowSelect } from './RowSelect.vue'
export { default as SelectableTableRow } from './SelectableTableRow.vue'
export { default as Sorting } from './Sorting.vue'

export interface SelectableRows<T = string | number> {
  selection: Ref<Array<T>>
  somethingSelected: ComputedRef<boolean>
  everythingSelected: ComputedRef<boolean>
  selectedCount: ComputedRef<number>
  totalCount: ComputedRef<number>
  clearSelection: () => void
  selectEverything: () => void
  withSelection: (callback: (selection: Array<T>) => void) => void
  availableRows: ComputedRef<Array<T>>
  disabledRows: ComputedRef<Array<T>>
}

export function useSelectableRows<T = string | number>(
  available: ComputedRef<Array<T>>,
  disabled?: ComputedRef<Array<T>>
): SelectableRows<T> {
  const selection = ref<Array<T>>([]) as Ref<Array<T>>

  // Set to null, when rows are changed.
  watch(available, () => {
    selection.value = []
  })

  const disabledRows = disabled || computed(() => [])

  const selectedCount = computed(() => selection.value.length)
  const totalCount = computed(() => available.value.length - disabledRows.value.length)
  const somethingSelected = computed(() => selectedCount.value > 0)
  const everythingSelected = computed(() => somethingSelected.value && selectedCount.value == totalCount.value)

  const clearSelection = () => {
    selection.value = []
  }

  const isValueSelected = (value: T) => selection.value.includes(value)
  const isValueDisabled = (value: T) => disabledRows.value.includes(value)

  const selectEverything = () => {
    available.value.forEach(it => {
      if (!isValueSelected(it) && !isValueDisabled(it)) {
        selection.value.push(it)
      }
    })
  }

  /**
   * Run given callback when selection is not empty.
   */
  const withSelection = (callback: (selection: Array<T>) => void) => {
    const selected = selection.value

    if (selected.length > 0) {
      callback(selected)
    }
  }

  return {
    selection,
    somethingSelected,
    everythingSelected,
    selectEverything,
    selectedCount,
    totalCount,
    clearSelection,
    withSelection,
    availableRows: available,
    disabledRows,
  }
}
