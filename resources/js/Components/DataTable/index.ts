import { registerNamespacedComponents } from "@stacktrace/ui";
import type { DefineComponent, Plugin } from "vue";
import { type TextStyle, configureStyle } from './internal'

export { default as DataTable } from './DataTable.vue'
export { default as DataTableResourceActions } from './DataTableResourceActions.vue'

export type { DataTableValue, DataTableResourceActionsValue } from './internal'

export const DataTablePlugin: Plugin = {
  install: app => {
    const columns = import.meta.glob<DefineComponent>('./Columns/**/*.vue', { eager: true })
    registerNamespacedComponents(app, columns, 'DataTable')

    const filters = import.meta.glob<DefineComponent>('./Filters/**/*.vue', { eager: true })
    registerNamespacedComponents(app, filters, 'DataTable')
  }
}

export type TableRowStyleProperty = 'highlight'
export type TableRowStyle = Record<TableRowStyleProperty | string, string | null>

export const createRowStyle = (style: TableRowStyle) => configureStyle(style, {
  highlight: {
    default: 'hover:bg-muted/50 data-[state=selected]:bg-muted',
    muted: 'bg-muted/20 text-muted-foreground hover:bg-muted/50 data-[state=selected]:bg-muted',
    destructive: 'bg-destructive/10 text-destructive hover:bg-destructive/20 data-[state=selected]:bg-destructive/30',
  }
})

export const createCellStyle = (style: TextStyle) => configureStyle(style, {
  fontWeight: {
    thin: 'font-thin',
    extralight: 'font-extralight',
    light: 'font-light',
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold',
    extrabold: 'font-extrabold',
    black: 'font-black',
  },
  fontFamily: {
    sans: 'font-sans',
    serif: 'font-serif',
    mono: 'font-mono',
  },
  whitespace: {
    normal: 'whitespace-normal',
    nowrap: 'whitespace-nowrap',
    pre: 'whitespace-pre',
    preLine: 'whitespace-pre-line',
    preWrap: 'whitespace-pre-wrap',
    breakSpaces: 'whitespace-break-spaces',
  },
  fontVariantNumeric: {
    normal: 'normal-nums',
    ordinal: 'ordinal',
    slashedZero: 'slashed-zero',
    lining: 'lining-nums',
    oldStyle: 'oldstyle-nums',
    proportional: 'proportional-nums',
    tabular: 'tabular-nums',
    diagonalFractions: 'diagonal-fractions',
    stackedFractions: 'stacked-fractions',
  },
  textDecorationLine: {
    underline: 'underline',
    overline: 'overline',
    lineThrough: 'line-through',
    noUnderline: 'no-underline',
  },
  verticalAlign: {
    baseline: 'align-baseline',
    top: 'align-top',
    middle: 'align-middle',
    bottom: 'align-bottom',
    textTop: 'align-text-top',
    textBottom: 'align-text-bottom',
    sub: 'align-sub',
    super: 'align-super',
  },
  textAlign: {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
    justify: 'text-justify',
    start: 'text-start',
    end: 'text-end',
  },
  fontStyle: {
    italic: 'italic',
    notItalic: 'not-italic',
  },
  color: {
    foreground: 'text-foreground',
    muted: 'text-muted-foreground',
  },
})

export const createHeadingStyle = createCellStyle
