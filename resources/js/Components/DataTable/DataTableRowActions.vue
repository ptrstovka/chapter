<template>
  <div class="flex flex-row justify-end gap-1" v-if="inlineActions.length > 0 || menuActions.length > 0">
    <DataTableActionButton
      v-for="action in inlineActions"
      :action="action"
      :selection="selection"
      @event="onEvent"
    />

    <DropdownMenu v-if="menuActions.length > 0">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" class="px-2 data-[state=open]:bg-muted" size="sm"><EllipsisIcon class="w-4 h-4" /></Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DataTableActionDropdownMenuItem
          v-for="action in menuActions"
          :action="action"
          :selection="selection"
          @event="onEvent"
          @exec="onExecAction"
        />
      </DropdownMenuContent>
    </DropdownMenu>

    <DataTableActionDialog
      v-if="actionToRun"
      :control="actionDialog"
      :action="actionToRun"
      :selection="selection"
    />
  </div>
</template>

<script setup lang="ts" generic="ResourceKey = string | number, ResourceValue = object">
import { Button } from '@/Components/Button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/DropdownMenu'
import { useToggle } from '@stacktrace/ui'
import { EllipsisIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { type ExecutableAction, type Row, useActionRunner } from './internal'
import DataTableActionDropdownMenuItem from './DataTableActionDropdownMenuItem.vue'
import DataTableActionButton from './DataTableActionButton.vue'
import DataTableActionDialog from './DataTableActionDialog.vue'

const emit = defineEmits(['event'])

const props = defineProps<{
  row: Row<ResourceKey, ResourceValue>
}>()

const onEvent = (event: string) => {
  emit('event', { name: event, selection: [props.row.key] })
}

const { run } = useActionRunner<ResourceKey>()
const actionToRun = ref<ExecutableAction>()
const actionDialog = useToggle()
const onExecAction = (action: ExecutableAction) => {
  if (action.isInline) {
    run(action, [props.row.key])
    return
  } else {
    actionToRun.value = action
    actionDialog.activate()
  }
}

const selection = computed(() => [props.row.key])
const inlineActions = computed(() => props.row.actions.filter(it => it.isInline && it.canRun))
const menuActions = computed(() => props.row.actions.filter(it => !it.isInline && it.canRun))
</script>
