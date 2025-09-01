import { cva, type VariantProps } from 'class-variance-authority'

export { default as Badge } from './Badge.vue'

export const badgeVariants = cva(
  'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden',
  {
    variants: {
      variant: {
        default: 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-50 dark:border-gray-800',
        primary: 'bg-primary text-primary-foreground border-primary',
        secondary: 'border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90',
        positive: 'bg-positive-foreground text-positive border-positive/20 dark:bg-positive dark:text-positive-foreground dark:border-positive',
        destructive: 'bg-red-50 border-red-200 text-red-700 dark:bg-red-800 dark:text-red-50 dark:border-red-800',
        warning: 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-50 dark:border-amber-800',
        outline: 'text-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
