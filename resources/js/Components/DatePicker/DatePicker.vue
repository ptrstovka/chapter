<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        plain
        variant="outline"
        size="default"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !date && 'text-muted-foreground',
          $attrs.class || ''
        )"
      >
        <CalendarIcon class="text-foreground h-4 w-4" />
        {{ date ? df.format(date.toDate(getLocalTimeZone())) : (placeholder || "Pick a date") }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" :to="to">
      <Calendar v-model="date" initial-focus />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { Button } from '@/Components/Button'
import { Calendar } from '@/Components/Calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/Popover'
import { cn } from '@/Utils'
import {
  DateFormatter,
  type DateValue,
  parseDate,
  getLocalTimeZone,
} from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { computed, ref, type Ref, watch } from 'vue'

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue?: string | null | undefined
  placeholder?: string | null | undefined
  to?: string | HTMLElement
}>()

const df = new DateFormatter('en-US', {
  dateStyle: 'long',
})

const date = ref(props.modelValue ? parseDate(props.modelValue) : undefined) as Ref<DateValue | undefined>

watch(date, newDate => {
  const updatedValue = newDate?.toString() || undefined

  if (updatedValue !== props.modelValue) {
    emit('update:modelValue', updatedValue)
  }
})

watch(computed(() => props.modelValue), newModelValue => {
  const currentDate = date.value?.toString() || undefined
  const updatedDate = newModelValue || undefined

  if (currentDate !== updatedDate) {
    date.value = updatedDate ? (parseDate(updatedDate) as DateValue) : undefined
  }
})
</script>
