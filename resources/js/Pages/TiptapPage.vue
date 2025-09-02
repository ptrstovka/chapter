<template>
  <div class="mx-auto max-w-4xl p-6 flex flex-col gap-6">
    <TiptapEditor v-if="visible" v-model="value" :disabled="disabled" />

    <div class="inline-flex flex-row gap-2">
      <Button @click="setValue">Set HTML</Button>
      <Button @click="disabled = !disabled">{{ disabled ? 'Enable' : 'Disable' }}</Button>
      <Button @click="visible = !visible">{{ visible ? 'Hide' : 'Show' }}</Button>
    </div>

    <code class="text-xs">{{ value || 'No content' }}</code>
  </div>
</template>

<script setup lang="ts">
import { TiptapEditor } from '@/Components/Tiptap'
import { Button } from "@/Components/Button";
import { ref } from "vue";

const value = ref<string | undefined>('<p>This is random HTML content.</p>')
const disabled = ref(false)
const visible = ref(true)

const setValue = () => {
  value.value = '<p>This is content</p>'
}
</script>
