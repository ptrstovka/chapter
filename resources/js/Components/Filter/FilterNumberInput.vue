<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        plain
        variant="outline"
        class="border-dashed border-input h-8"
      >
        <HashIcon class="w-4 h-4 mr-2" />
        {{ label }}

        <template v-if="currentValue">
          <Separator orientation="vertical" class="mx-2 h-4" />
          <Badge variant="secondary" class="rounded-sm px-1 font-normal">{{ currentValue }}</Badge>
        </template>

      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-60 p-0" align="start">
      <div class="p-2">
        <Select v-model="operator" @update:model-value="onOperatorChange">
          <SelectTrigger class="h-7 mb-2">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="lt">less than</SelectItem>
            <SelectItem value="lte">less then or equal</SelectItem>
            <SelectItem value="eq">equal to</SelectItem>
            <SelectItem value="gte">greater than or equal</SelectItem>
            <SelectItem value="gt">greater than</SelectItem>
            <SelectItem value="be">between</SelectItem>
            <SelectItem value="nbe">not between</SelectItem>
          </SelectContent>
        </Select>

        <div class="grid grid-cols-2 gap-2" v-if="operator == 'be' || operator == 'nbe'">
          <Input type="number" placeholder="From" v-model="inputFrom" class="h-7" />
          <Input type="number" placeholder="To" v-model="inputTo" class="h-7" @keydown.enter="open = false" />
        </div>

        <Input v-else type="number" placeholder="Value" v-model="input" class="h-7" @keydown.enter="open = false" />
      </div>

      <div class="border-t p-1">
        <Button @click="clear" size="sm" class="w-full" variant="ghost">Clear</Button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { HashIcon } from "lucide-vue-next";
import { Popover, PopoverTrigger, PopoverContent } from '@/Components/Popover'
import { Separator } from '@/Components/Separator'
import { Button } from '@/Components/Button'
import { Input } from '@/Components/Input'
import { Select, SelectValue, SelectItem, SelectContent, SelectTrigger } from '@/Components/Select'
import { Badge } from '@/Components/Badge'

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  title: string
  modelValue?: string | null
}>()

const open = ref(false)

const label = computed(() => props.title)

const operator = ref('eq')
const input = ref()
const inputFrom = ref()
const inputTo = ref()

const setDefaults = () => {
  operator.value = 'eq'
  input.value = ''
  inputFrom.value = ''
  inputTo.value = ''
}

const isValidOperator = (operator: string) => {
  return ['lt', 'lte', 'eq', 'gt', 'gte'].includes(operator)
}

const asNumber = (value: any) => {
  const numeric = parseFloat(value)

  if (!isNaN(numeric) && isFinite(numeric)) {
    return numeric
  }

  return null
}

const createModelValue = () => {
  if (operator.value == 'be' || operator.value == 'nbe') {
    const from = asNumber(inputFrom.value)
    const to = asNumber(inputTo.value)

    if (from !== null && to !== null) {
      return `${operator.value}:${from}:${to}`
    }
  } else {
    const value = asNumber(input.value)

    if (value !== null) {
      return `${operator.value}:${value}`
    }
  }

  return null
}

const parseModelValue = (value: string | null | undefined) => {
  if (! value || ! value.includes(':')) {
    return null
  }

  const parts = value.split(':')
  if (parts.length == 2) {
    const op = isValidOperator(parts[0]) ? parts[0] : null
    const value = asNumber(parts[1])

    if (op && value !== null) {
      return { op, value, from: '', to: '' }
    }
  } else if (parts.length === 3) {
    const op = parts[0] == 'be' || parts[0] == 'nbe' ? parts[0] : null
    const from = asNumber(parts[1])
    const to = asNumber(parts[2])

    if (op && from !== null && to !== null) {
      return { op, value, from, to }
    }
  }

  return null
}

const setModelValue = (value: string | null | undefined) => {
  const parsed = parseModelValue(value)

  if (parsed) {
    operator.value = parsed.op
    input.value = parsed.value
    inputFrom.value = parsed.from
    inputTo.value = parsed.to
  } else {
    setDefaults()
  }
}

setModelValue(props.modelValue)

watch(() => props.modelValue, value => {
  const current = createModelValue()
  if (current !== value) {
    setModelValue(value)
  }
})

const currentValue = computed(() => {
  const value = parseModelValue(props.modelValue)

  if (!value) {
    return null
  }

  if (value.op == 'be') {
    return `${value.from} > < ${value.to}`
  }

  if (value.op == 'nbe') {
    return `${value.from} < > ${value.to}`
  }

  const sign = {
    'lt': '<',
    'lte': '<=',
    'eq': '',
    'gte': '>=',
    'gt': '>'
  }[value.op]

  return `${sign || ''} ${value.value}`.trim()
})

const onOperatorChange = () => {
  const value = createModelValue()

  if (value !== null) {
    emit('update:modelValue', value)
  }
}

const emitCurrentValue = () => {
  emit('update:modelValue', createModelValue())
}

const clear = () => {
  setDefaults()
  emitCurrentValue()
}

watch(open, isOpen => {
  if (! isOpen) {
    emitCurrentValue()
  }
})
</script>
