<template>
  <TiptapProvider v-bind="forwarded" data-slot="tiptap">
    <TiptapToolbar>
      <TiptapHeadingDropdownMenu :levels="[1, 2, 3, 4]" />

      <TiptapToolbarSeparator />

      <TiptapMarkButton type="bold" />
      <TiptapMarkButton type="italic" />
      <TiptapMarkButton type="strike" />
      <TiptapMarkButton type="code" />
      <TiptapMarkButton type="underline" />

      <TiptapToolbarSeparator />

      <TiptapMarkButton type="superscript" />
      <TiptapMarkButton type="subscript" />

      <TiptapToolbarSeparator />

      <TiptapTextAlignButton align="left" />
      <TiptapTextAlignButton align="center" />
      <TiptapTextAlignButton align="right" />
      <TiptapTextAlignButton align="justify" />
    </TiptapToolbar>

    <TiptapContent />
  </TiptapProvider>
</template>

<script setup lang="ts">
import { useForwardPropsEmits } from "reka-ui";
import {
  TiptapProvider,
  TiptapContent,
  TiptapToolbar,
  TiptapMarkButton,
  TiptapHeadingDropdownMenu,
  TiptapToolbarSeparator,
  TiptapTextAlignButton,
} from ".";
import type { TiptapProviderProps, TiptapProviderEmits } from ".";

const emit = defineEmits<TiptapProviderEmits>()
const props = defineProps<TiptapProviderProps>()

const forwarded = useForwardPropsEmits(props, emit)
</script>
