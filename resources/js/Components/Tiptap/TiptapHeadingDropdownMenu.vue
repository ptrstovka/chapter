<template>
  <DropdownMenu v-if="show">
    <DropdownMenuTrigger as-child>
      <TiptapButton
        :disabled="isDisabled"
        :active="isAnyHeadingActive"
        tabindex="-1"
        aria-label="Format text as heading"
        :aria-pressed="isAnyHeadingActive"
        tooltip="Heading"
        class="gap-0.5"
      >
        <component :is="icon" />
        <ChevronDownIcon class="size-3" />
      </TiptapButton>
    </DropdownMenuTrigger>
    <DropdownMenuContent @close-auto-focus.prevent>
      <DropdownMenuGroup>
        <DropdownMenuItem
          v-for="level in levels"
          as-child
        >
          <TiptapHeadingButton
            :level="level"
            :text="getFormattedHeadingName(level)"
            :tooltip="''"
            class="w-full justify-start"
          />
        </DropdownMenuItem>
      </DropdownMenuGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem
} from "@/Components/DropdownMenu";
import { isNodeSelection } from "@tiptap/vue-3";
import { isNodeInSchema, useTiptap } from "./utils.ts";
import { computed } from "vue";
import { TiptapButton, TiptapHeadingButton } from ".";
import {
  Heading1Icon,
  Heading2Icon,
  Heading3Icon,
  Heading4Icon,
  Heading5Icon,
  Heading6Icon,
  HeadingIcon,
  ChevronDownIcon
} from "lucide-vue-next";

type Level = 1 | 2 | 3 | 4 | 5 | 6

const props = withDefaults(defineProps<{
  levels?: Array<Level>
  hideWhenUnavailable?: boolean
}>(), {
  levels: () => [1, 2, 3, 4, 5, 6],
  hideWhenUnavailable: false,
})

const { editor, disabled: editorDisabled } = useTiptap()

const canToggleAnyHeading = computed(() => props.levels.some(level => editor.value?.can().toggleNode("heading", "paragraph", { level })))

const headingInSchema = computed(() => isNodeInSchema('heading', editor.value || null))
const isDisabled = computed(() => editorDisabled.value || !canToggleAnyHeading.value)
const isAnyHeadingActive = computed(() => !isDisabled.value && (editor.value?.isActive("heading") ?? false))

const icon = computed(() => {
  const activeLevel = props.levels.find((level) => editor.value?.isActive("heading", { level })) as Level | undefined

  if (activeLevel && !isDisabled.value) {
    return {
      1: Heading1Icon,
      2: Heading2Icon,
      3: Heading3Icon,
      4: Heading4Icon,
      5: Heading5Icon,
      6: Heading6Icon,
    }[activeLevel]
  }

  return HeadingIcon
})

const getFormattedHeadingName = (level: Level) => `Heading ${level}`

const show = computed(() => {
  if (!headingInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection) || !canToggleAnyHeading.value) {
      return false
    }
  }

  return true
})
</script>
