<template>
  <EditorContent :editor="editor" />
</template>

<script setup lang="ts">
import { EditorContent } from '@tiptap/vue-3'
import { useTiptap } from './utils.ts'

const { editor } = useTiptap()
</script>
