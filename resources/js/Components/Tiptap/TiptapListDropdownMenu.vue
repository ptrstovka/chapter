<template>
  <DropdownMenu v-if="show">
    <DropdownMenuTrigger as-child>
      <TiptapButton
        :disabled="isDisabled"
        :active="isActive"
        tabindex="-1"
        :aria-label="$t('Tiptap:List options')"
        :aria-pressed="isActive"
        :tooltip="$t('Tiptap:List')"
        class="gap-0.5"
      >
        <component :is="icon" />
        <ChevronDownIcon class="size-3" />
      </TiptapButton>
    </DropdownMenuTrigger>
    <DropdownMenuContent @close-auto-focus.prevent>
      <DropdownMenuGroup>
        <DropdownMenuItem
          v-for="type in types"
          as-child
        >
          <TiptapListButton
            :type="type"
            :text="resolveLabel(type)"
            :hide-when-unavailable="hideWhenUnavailable"
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
  DropdownMenuContent,
  DropdownMenuGroup, DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/DropdownMenu";
import { isNodeInSchema, useTiptap } from "@/Components/Tiptap/utils.ts";
import { isNodeSelection } from "@tiptap/vue-3";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";
import { type TiptapButtonProps, TiptapButton, TiptapListButton } from '.'
import { ChevronDownIcon, ListIcon, ListOrderedIcon, ListTodoIcon } from "lucide-vue-next";

type ListType = "bulletList" | "orderedList" | "taskList"

const props = withDefaults(defineProps<TiptapButtonProps & {
  /**
   * The list types to display in the dropdown.
   */
  types?: Array<ListType>

  /**
   * Whether the dropdown should be hidden when no list types are available
   * @default false
   */
  hideWhenUnavailable?: boolean
}>(), {
  types: () => ['bulletList', 'orderedList', 'taskList']
})

const { editor, disabled: editorDisabled } = useTiptap()

const canToggleList = (type: ListType) => {
  if (! editor.value) {
    return false
  }

  switch (type) {
    case "bulletList":
      return editor.value.can().toggleBulletList()
    case "orderedList":
      return editor.value.can().toggleOrderedList()
    case "taskList":
      return editor.value.can().toggleList("taskList", "taskItem")
    default:
      return false
  }
}

const canToggleAnyList = computed(() => {
  if (!editor.value) {
    return false
  }

  return props.types.some((type) => canToggleList(type))
})

const listInSchema = computed(() => props.types.some(it => isNodeInSchema(it, editor.value || null)))

const isListActive = (type: ListType) => {
  if (! editor.value) {
    return false
  }

  switch (type) {
    case "bulletList":
      return editor.value.isActive("bulletList")
    case "orderedList":
      return editor.value.isActive("orderedList")
    case "taskList":
      return editor.value.isActive("taskList")
    default:
      return false
  }
}

const show = computed(() => {
  if (!listInSchema.value || !editor.value) {
    return false
  }

  if (props.hideWhenUnavailable) {
    if (isNodeSelection(editor.value.state.selection) || !canToggleAnyList.value) {
      return false
    }
  }

  return true
})

const resolveLabel = (type: ListType) => {
  return {
    bulletList: trans('Tiptap:Bullet List'),
    orderedList: trans('Tiptap:Ordered List'),
    taskList: trans('Tiptap:Task List'),
  }[type]
}

const isDisabled = computed(() => {
  if (!editor.value) return true
  if (props.disabled) return true
  if (editorDisabled.value) return true
  if (!canToggleAnyList.value) return true
  return false
})

const isActive = computed(() => !isDisabled.value && props.types.some(it => isListActive(it)))

const icon = computed(() => {
  const activeType = props.types.find(it => isListActive(it))

  if (activeType && !isDisabled.value) {
    return {
      bulletList: ListIcon,
      orderedList: ListOrderedIcon,
      taskList: ListTodoIcon,
    }[activeType]
  }

  return ListIcon
})
</script>
