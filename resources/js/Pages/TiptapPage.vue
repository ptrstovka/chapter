<template>
  <Head title="Tiptap Playground" />

  <div class="mx-auto max-w-4xl p-6 flex flex-col gap-6">
    <TiptapSimpleEditor v-if="visible" v-model="value" :disabled="disabled" />

    <div class="inline-flex flex-row gap-2">
      <Button @click="setValue">Set HTML</Button>
      <Button @click="disabled = !disabled">{{ disabled ? 'Enable' : 'Disable' }}</Button>
      <Button @click="visible = !visible">{{ visible ? 'Hide' : 'Show' }}</Button>
    </div>

    <code class="text-xs">{{ value || 'No content' }}</code>
  </div>
</template>

<script setup lang="ts">
import { TiptapSimpleEditor } from '@/Components/Tiptap'
import { Button } from "@/Components/Button";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";

const value = ref<string | undefined>('<h1>Hello world!</h1> <p>Lorem Ipsum is <a href="https://www.stacktrace.sk">simply</a> dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>')
const disabled = ref(false)
const visible = ref(true)

const setValue = () => {
  value.value = '<p>This is content</p>'
}
</script>
