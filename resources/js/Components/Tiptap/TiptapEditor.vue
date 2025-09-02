<template>
  <TiptapProvider v-bind="forwarded" data-slot="tiptap">
    <TiptapToolbar>
      <TiptapMarkButton type="bold" />
      <TiptapMarkButton type="italic" />
      <TiptapMarkButton type="strike" />
      <TiptapMarkButton type="code" />
      <TiptapMarkButton type="underline" />

      <div class="h-5 px-1">
        <Separator orientation="vertical" />
      </div>

      <TiptapMarkButton type="superscript" />
      <TiptapMarkButton type="subscript" />
    </TiptapToolbar>

    <TiptapContent />
  </TiptapProvider>
</template>

<script setup lang="ts">
import { useForwardPropsEmits } from "reka-ui";
import { TiptapProvider, TiptapContent, TiptapToolbar, TiptapMarkButton } from '.'
import type { TiptapProviderProps, TiptapProviderEmits } from '.'
import { Separator } from '@/Components/Separator'

const emit = defineEmits<TiptapProviderEmits>()
const props = defineProps<TiptapProviderProps>()

const forwarded = useForwardPropsEmits(props, emit)
</script>
