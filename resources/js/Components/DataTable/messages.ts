import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

export default computed(() => ({
  searchPlaceholder: trans('Search…'),
  emptyTableTitle: trans('Nothing to see there.'),
  emptyTableDescription: trans('The table is empty.'),
  selectedRows: (selected: number, total: number) => trans('Selected :selected of :total', { selected: `${selected}`, total: `${total}` }),
  actions: trans('DataTableActions'),
  cancelSelection: trans('Cancel selection'),
  viewOptions: trans('View options'),
  perPage: trans('Per page'),
  perPageOption: (value: number) => trans(':value results', { value: `${value}` }),
  paginatorTotal: trans('PaginatorTotal'),
  paginatorOf: trans('PaginatorOf'),
  paginatorPrevious: trans('PaginatorPrevious'),
  paginatorNext: trans('Next'),
  searchEmptyTitle: trans('No records found.'),
  searchEmptyDescription: trans('Try to adjust your search criteria.'),
  clearSearch: trans('Clear Search'),
}))
