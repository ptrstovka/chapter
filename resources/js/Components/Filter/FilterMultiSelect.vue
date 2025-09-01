<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button plain variant="outline" class="h-8 border-input border-dashed">
        <PlusCircleIcon class="mr-2 h-4 w-4" />
        {{ title }}
        <template v-if="selectedValues.length > 0">
          <Separator orientation="vertical" class="mx-2 h-4" />
          <Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">{{ selectedValues.length }}</Badge>
          <div class="hidden space-x-1 lg:flex">
            <Badge v-if="selectedValues.length > 2" variant="secondary" class="rounded-sm px-1 font-normal">{{ selectedValues.length }} vybrané</Badge>
            <template v-else>
              <Badge v-for="option in selectedValues" :key="`${option.value}`" variant="secondary" class="rounded-sm px-1 font-normal">{{ option.label }}</Badge>
            </template>
          </div>
        </template>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[250px] p-0" align="start">
      <Command :filter-function="onFilter">
        <CommandInput class="h-9" :placeholder="title" />
        <CommandList>
          <CommandGroup>
            <CommandItem v-for="option in options" :key="`${option.value}`" :value="option" @select="onSelect(option)">
              <Checkbox class="mr-2" :model-value="isSelected(option)" />
              <span>{{ option.label }}</span>
            </CommandItem>
          </CommandGroup>

          <template v-if="selectedValues.length >0">
            <CommandSeparator />
            <CommandGroup>
              <CommandItem :value="{ label: 'Clear' }" class="justify-center text-center" @select="onClear">Clear</CommandItem>
            </CommandGroup>
          </template>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { PlusCircleIcon } from "lucide-vue-next";
import { computed } from "vue";
import type { SelectOption } from "@stacktrace/ui";
import { Popover, PopoverTrigger, PopoverContent } from '@/Components/Popover'
import { Separator } from '@/Components/Separator'
import { Button } from '@/Components/Button'
import { Badge } from '@/Components/Badge'
import { Command, CommandInput, CommandList, CommandGroup, CommandItem, CommandSeparator } from '@/Components/Command'
import { Checkbox } from "@/Components/Checkbox"

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  title: string
  options: Array<SelectOption>
  modelValue?: Array<string>
  disabled?: boolean
}>()

const isSelected = (option: SelectOption) => props.modelValue?.includes(`${option.value}`)

const selectedValues = computed(() => props.options.filter(it => isSelected(it)))

const onFilter: any = (val: Array<SelectOption>, term: string) => val.filter(it => it.label.toLowerCase().includes(term))

const onSelect = (option: SelectOption) => {
  if (props.disabled) {
    return
  }

  const newValue = props.modelValue ? [...props.modelValue] : []

  const strValue = `${option.value}`

  if (newValue.includes(strValue)) {
    newValue.splice(newValue.indexOf(strValue), 1)
  } else {
    newValue.push(strValue)
  }

  emit('update:modelValue', newValue)
}

const onClear = () =>{
  if (props.disabled) {
    return
  }

  emit('update:modelValue', [])
}
</script>
