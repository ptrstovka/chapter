<template>
  <Checkbox
      :indeterminate="isIndeterminate"
      :model-value="isChecked"
      @click="onChange"
  />
</template>

<script setup lang="ts">
import { type SelectableRows } from "./";
import { computed, nextTick } from "vue";
import { Checkbox } from "@/Components/Checkbox";

const props = defineProps<{
  selectable: SelectableRows
}>()

const isIndeterminate = computed(() => props.selectable.somethingSelected.value && !props.selectable.everythingSelected.value)
const isChecked = computed(() => props.selectable.everythingSelected.value)

const onChange = () =>{
  nextTick(() => {
    if (props.selectable.everythingSelected.value) {
      props.selectable.clearSelection()
    } else {
      props.selectable.selectEverything()
    }
  })
}
</script>
