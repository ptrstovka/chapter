import { cva, type VariantProps } from 'class-variance-authority'

export { default as Badge } from './Badge.vue'

export const badgeVariants = cva(
  'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    variants: {
      variant: {
        default: 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-50 dark:border-gray-800',
        secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
        positive: 'bg-green-50 border-green-200 text-green-700 dark:bg-green-800 dark:text-green-50 dark:border-green-800',
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
