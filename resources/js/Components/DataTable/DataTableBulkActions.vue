<template>
  <template v-if="showBulkActions">
    <DataTableActionButton
      v-for="action in inlineActions"
      :action="action"
      :selection="selection"
      @event="onEvent"
    />

    <DropdownMenu v-if="menuActions.length > 0">
      <DropdownMenuTrigger as-child>
        <Button size="sm"><ChevronDownIcon class="w-4 h-4 mr-1" /> {{ messages.actions }}</Button>
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
  </template>

  <DataTableActionDialog
    v-if="actionToRun"
    :control="actionDialog"
    :action="actionToRun"
    :selection="selection"
  />
</template>

<script setup lang="ts" generic="ResourceKey = string | number">
import { Button } from '@/Components/Button'
import messages from './messages'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/DropdownMenu'
import { useToggle } from '@stacktrace/ui'
import { ChevronDownIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { type ExecutableAction, injectContext, useActionRunner } from './internal'
import DataTableActionDropdownMenuItem from './DataTableActionDropdownMenuItem.vue'
import DataTableActionButton from './DataTableActionButton.vue'
import DataTableActionDialog from './DataTableActionDialog.vue'

const emit = defineEmits(['event'])

const { showBulkActions, selectableRows, bulkActions } = injectContext()

const selection = computed(() => selectableRows.selection.value as Array<ResourceKey>)

const onEvent = (event: string) => {
  emit('event', { name: event, selection: [...selection.value] })
}

const { run } = useActionRunner<ResourceKey>()
const actionToRun = ref<ExecutableAction>()
const actionDialog = useToggle()
const onExecAction = (action: ExecutableAction) => {
  if (action.isInline) {
    run(action, [...selection.value])
    return
  } else {
    actionToRun.value = action
    actionDialog.activate()
  }
}

const inlineActions = computed(() => bulkActions.value.filter(it => it.canRun && it.isInline && it.isBulk))
const menuActions = computed(() => bulkActions.value.filter(it => it.canRun && !it.isInline && it.isBulk))
</script>
