<template>
  <div class="focus-within:ring-[3px] focus-within:border-ring focus-within:ring-ring/50 border border-input rounded-md shadow-xs transition-[color,box-shadow] overflow-hidden">
    <TiptapEditor
      v-if="contentType === 'html'"
      v-model="content"
    />

    <Textarea
      v-else-if="contentType === 'markdown'"
      v-model="content"
      class="border-0 shadow-none focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0 min-h-60 font-mono"
    />

    <div class="border-t border-input flex items-center justify-end px-3 py-1.5">
      <div class="inline-flex items-center gap-2">
        <span class="text-xs font-medium">{{ $t('Rich Text') }}</span>
        <Switch
          v-model="isMarkdown"
          class="data-[state=unchecked]:bg-primary dark:data-[state=unchecked]:bg-primary h-[12px] w-6"
          thumb="dark:data-[state=unchecked]:bg-primary-foreground size-2 data-[state=checked]:translate-x-[13px] data-[state=unchecked]:translate-x-[1px]"
        />
        <span class="text-xs font-medium">{{ $t('Markdown') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Switch } from "@/Components/Switch";
import { Textarea } from "@/Components/Textarea";
import type { TextContentType } from "@/Types"
import { ref, watch } from "vue";
import TiptapEditor from "./TiptapEditor.vue"

const emit = defineEmits(['update:content', 'update:contentType'])

const props = defineProps<{
  content: string | null
  contentType: TextContentType
}>()

const content = ref<string | undefined>(props.content || undefined)

const isMarkdown = ref(props.contentType === 'markdown')

watch(isMarkdown, markdownSelected => {
  if (markdownSelected) {
    if (props.contentType != 'markdown') {
      content.value = ''
      emit('update:content', '')
      emit('update:contentType', 'markdown')
    }
  } else {
    if (props.contentType != 'html') {
      content.value = ''
      emit('update:content', '')
      emit('update:contentType', 'html')
    }
  }
})

watch(content, newContent => {
  if (props.content !== newContent) {
    emit('update:content', newContent)
  }
})

watch(() => props.content, newContent => {
  const updatedContent = newContent || undefined
  if (content.value !== updatedContent) {
    content.value = updatedContent
  }
})

watch(() => props.contentType, newContentType => {
  const isNewContentTypeMarkdown = newContentType === 'markdown'

  if (isNewContentTypeMarkdown != isMarkdown.value) {
    isMarkdown.value = isNewContentTypeMarkdown
  }
})
</script>
