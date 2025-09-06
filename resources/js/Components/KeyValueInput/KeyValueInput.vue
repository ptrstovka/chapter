<template>
  <div class="rounded-md dark:bg-input/30 border border-input shadow-xs overflow-hidden divide-y divide-input">
    <div class="relative">
      <div class="grid grid-cols-2 bg-accent/40 dark:bg-accent/20 divide-x divide-input h-9">
        <div class="px-3 text-sm font-semibold flex items-center">{{ $t('Key') }}</div>
        <div class="px-3 text-sm font-semibold flex items-center">{{ $t('Value') }}</div>
      </div>

      <Button v-if="!disabled" @click="add" class="absolute h-auto p-1.5 has-[>svg]:px-1.5 right-1 top-1/2 -translate-y-1/2" variant="ghost" plain>
        <CirclePlusIcon />
      </Button>
    </div>

    <div v-for="(value, idx) in state.values" class="relative">
      <div class="grid grid-cols-2 divide-x divide-input">
        <div class="flex items-center">
          <Input :disabled="disabled" class="font-mono rounded-none shadow-none focus-visible:ring-0 focus-visible:border-0 border-0" v-model="value.key" />
        </div>
        <div class="flex items-center">
          <Input :disabled="disabled" :class="{ 'pr-8': !disabled }" class="font-mono rounded-none shadow-none focus-visible:ring-0 focus-visible:border-0 border-0" v-model="value.value" />
        </div>
      </div>

      <Button v-if="!disabled" @click="remove(idx)" class="absolute h-auto p-1.5 has-[>svg]:px-1.5 right-1 top-1/2 -translate-y-1/2" variant="ghost-desctructive" plain>
        <CircleMinusIcon />
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { Input } from "@/Components/Input";
import { CirclePlusIcon, CircleMinusIcon } from 'lucide-vue-next'
import { reactive, toRaw, watch } from "vue";
import isEqual from 'lodash/isEqual'

const emit = defineEmits(['update:modelValue'])

const props = defineProps<{
  modelValue?: Record<string, string | null>
  disabled?: boolean
}>()

const toValueList: (value: Record<string, string | null>) => Array<{ key: string, value: string }> = value => {
  return Object.keys(value).map(key => ({
    key, value: value[key] || ''
  }))
}

const state = reactive<{ values: Array<{ key: string, value: string }> }>({
  values: toValueList(props.modelValue || {}),
})

const add = () => {
  state.values.push({ key: '', value: '' })
}

const remove = (index: number) => state.values.splice(index, 1)

const toRecord: (list: Array<{ key: string, value: string }>) => Record<string, string | null> = list => {
  const value: Record<string, string | null> = {}
  list.forEach(it => {
    if (it.key) {
      value[it.key] = it.value === '' ? null : it.value
    }
  })
  return value
}

watch(state, () => {
  const modelValue = toRaw(props.modelValue || {})
  const currentValue = toRaw(toRecord(state.values))

  if (! isEqual(modelValue, currentValue)) {
    emit('update:modelValue', currentValue)
  }
})

watch(() => props.modelValue, updatedModelValue => {
  const modelValue = toRaw(updatedModelValue || {})
  const currentValue = toRaw(toRecord(state.values))

  if (! isEqual(modelValue, currentValue)) {
    state.values = toValueList(modelValue)
  }
})
</script>
