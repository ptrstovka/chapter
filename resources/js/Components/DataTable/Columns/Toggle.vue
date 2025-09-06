<template>
  <SwitchToggle v-bind="props" />
</template>

<script setup lang="ts">
import { SwitchToggle, type SwitchToggleProps } from "@/Components/Switch";

const props = defineProps<SwitchToggleProps>()
</script>
