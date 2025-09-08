<template>
  <DateFieldRoot
    v-slot="{ segments }"
    class="flex items-center select-none border border-input shadow-xs px-3 h-9 text-sm bg-transparent rounded-md focus-visible:border-ring focus-visible:ring-ring/50"
  >
    <template
      v-for="item in segments"
      :key="item.part"
    >
      <DateFieldInput
        v-if="item.part === 'literal'"
        :part="item.part"
      >
        {{ item.value }}
      </DateFieldInput>
      <DateFieldInput
        v-else
        :part="item.part"
        class="rounded transition-[color,box-shadow] focus:outline-none focus:border-ring focus:ring-ring/50 focus:ring-[3px] border border-transparent focus:border-input data-[placeholder]:text-muted-foreground selection:bg-primary selection:text-primary-foreground"
      >
        {{ item.value }}
      </DateFieldInput>
    </template>
  </DateFieldRoot>
</template>

<script lang="ts" setup>
import { DateFieldInput, DateFieldRoot } from 'reka-ui'
</script>

