<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        plain
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        size="default"
        :class="cn('w-full justify-between text-left font-normal disabled:opacity-100 disabled:bg-accent/50', $attrs.class || '')"
        :disabled="disabled"
      >
        {{ value ? options.find((option) => option.value === value)?.label : placeholder }}
        <ChevronDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="p-0" style="width: var(--reka-popover-trigger-width)">
      <Command v-model:search-term="searchTerm">
        <CommandInput class="h-9" :placeholder="searchLabel" />
        <CommandEmpty>{{ notFoundLabel }}</CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="option in filteredOptions"
              :key="option.value"
              :value="option.value"
              @select="onSelected(option)"
            >
              {{ option.label }}
              <CheckIcon
                :class="cn(
                  'ml-auto h-4 w-4',
                  value === option.value ? 'opacity-100' : 'opacity-0',
                )"
              />
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
<script setup lang="ts" generic="V extends string | number">
import { Button } from '@/Components/Button'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/Command'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/Popover'
import { cn } from '@/Utils'
import { ChevronDownIcon, CheckIcon } from 'lucide-vue-next'
import { useVModel } from '@vueuse/core'
import { computed, ref, type Ref } from 'vue'
import { type SelectOption } from '@stacktrace/ui'

const emit = defineEmits(['update:modelValue'])

const props = withDefaults(defineProps<{
  options: Array<SelectOption<V>>
  searchLabel?: string | undefined
  placeholder?: string | null
  notFoundLabel?: string | null
  nullable?: boolean
  modelValue?: V | null
  disabled?: boolean
}>(), {
  searchLabel: 'Search options…',
  placeholder: 'Select option…',
  notFoundLabel: 'No options found.',
  nullable: false,
})

const open = ref(false)
const value = useVModel(props, 'modelValue', emit) as Ref<V | null>

const onSelected = (option: SelectOption<V>) => {
  if (props.nullable && value.value === option.value) {
    value.value = null
  } else {
    value.value = option.value
  }

  open.value = false
}

const searchTerm = ref('')
const filteredOptions = computed(() =>
  searchTerm.value == ''
    ? props.options
    : props.options.filter(it => it.label.toLowerCase().includes(searchTerm.value.toLowerCase())))
</script>
