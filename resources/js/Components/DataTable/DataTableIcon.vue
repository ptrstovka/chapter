<template>
  <svg v-bind="attributes" v-html="content"></svg>
</template>
<script setup lang="ts">
import { shallowRef, watch } from 'vue'

const props = defineProps<{
  src: string
}>()

const resolveAttributes = (element: HTMLElement) => {
  const attributes = element.attributes;
  const attributesObj: Record<string, any> = {}

  for (let i = 0; i < attributes.length; i++) {
    const attr = attributes[i];
    attributesObj[attr.name] = attr.value;
  }

  return attributesObj;
}

const parser = new DOMParser()
const element = parser.parseFromString(props.src, 'image/svg+xml').documentElement

const attributes = shallowRef(resolveAttributes(element))
const content = shallowRef(element.innerHTML)

watch(() => props.src, () => {
  const element = parser.parseFromString(props.src, 'image/svg+xml').documentElement

  attributes.value = resolveAttributes(element)
  content.value = element.innerHTML
})
</script>
