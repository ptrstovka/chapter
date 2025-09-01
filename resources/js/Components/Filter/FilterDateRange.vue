<template>
  <div :class="cn('grid gap-2', $attrs.class ?? '')">
    <Popover>
      <PopoverTrigger as-child>
        <Button
          plain
          id="date"
          :variant="'outline'"
          :class="cn(
            'border-dashed h-8',
            !isSelected && 'font-medium',
          )"
        >
          <CalendarIcon class="mr-2 h-4 w-4" />

          {{ title }}

          <template v-if="label">
            <Separator orientation="vertical" class="mx-2 h-4" />
            <Badge variant="secondary" class="rounded-sm px-1 font-normal">{{ label }}</Badge>
          </template>
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-0" align="start">
        <RangeCalendar locale="sk" v-model="date" initial-focus :number-of-months="2" />

        <div v-if="isSelected" class="px-4 pb-2">
          <Button @click="clear" class="w-full" variant="ghost">Clear</Button>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { Popover, PopoverTrigger, PopoverContent } from '@/Components/Popover'
import { Separator } from '@/Components/Separator'
import { RangeCalendar } from "@/Components/RangeCalendar";
import { Badge } from '@/Components/Badge'
import { Button } from "@/Components/Button";
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { computed, type Ref, ref, watch } from 'vue'
import { cn } from '@/Utils'
import {
  DateFormatter,
  parseDate,
  getLocalTimeZone,
} from '@internationalized/date'
import type { DateRange } from 'reka-ui'

const emit = defineEmits(['update:from', 'update:until'])

const props = defineProps<{
  title: string
  from?: string
  until?: string
}>()

const df = new DateFormatter('sk-SK', { // TODO: Locale Support
  dateStyle: 'medium',
})

const date = ref({
  start: props.from ? parseDate(props.from) : undefined,
  end: props.until ? parseDate(props.until) : undefined,
}) as Ref<DateRange>

const clear = () => {
  date.value = { start: undefined, end: undefined }
}

watch(date, newDate => {
  const start = newDate.start?.toString() || undefined
  const end = newDate.end?.toString() || undefined

  if (start !== props.from) {
    emit('update:from', start)
  }

  if (end !== props.until) {
    emit('update:until', end)
  }
})

watch(computed(() => props.from), newFrom => {
  const currentFrom = date.value.start?.toString() || undefined
  const updatedFrom = newFrom || undefined

  if (updatedFrom !== currentFrom) {
    date.value.start = updatedFrom ? parseDate(updatedFrom) : undefined
  }
})

watch(computed(() => props.until), newUntil => {
  const currentUntil = date.value.end?.toString() || undefined
  const updatedUntil = newUntil || undefined

  if (updatedUntil !== currentUntil) {
    date.value.end = updatedUntil ? parseDate(updatedUntil) : undefined
  }
})

const isSelected = computed(() => date.value.start || date.value.end)

const label = computed(() => {
  const from = date.value.start?.toDate(getLocalTimeZone()) || null
  const until = date.value.end?.toDate(getLocalTimeZone()) || null

  if (from && until) {
    const fromFormat = df.format(from)
    const untilFormat = df.format(until)

    if (fromFormat == untilFormat) {
      return fromFormat
    }

    return `${fromFormat} - ${untilFormat}`
  }

  return null
})
</script>
