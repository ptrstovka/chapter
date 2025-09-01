<template>
  <Checkbox v-model="selectable" :value="value" :disabled="disabled" />
</template>

<script setup lang="ts">
import { type ComputedRef, inject, type Ref } from "vue";
import { Checkbox } from "@/Components/Checkbox";

const selectable = inject<Ref<Array<string | number>>>('selectedValues', () => {
  throw new Error("The RowSelect must be inside SelectableTableRow")
}, true)

const value = inject<ComputedRef<string | number>>('value', () => {
  throw new Error("The SelectableTableRow does not have a value set.")
}, true)

const disabled = inject<ComputedRef<boolean>>('disabled', () => {
  throw new Error("The SelectableTableRow does not have a value set.")
}, true)
</script>
