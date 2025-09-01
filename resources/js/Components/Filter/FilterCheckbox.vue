<template>
  <Button plain @click="value = !value" variant="outline" class="h-8 border-dashed">
    <Checkbox :model-value="value" class="mr-2 -ml-1" /> {{ title }}
  </Button>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { Checkbox } from "@/Components/Checkbox";

const value = defineModel({ required: false })

defineProps<{
  title: string
}>()
</script>
