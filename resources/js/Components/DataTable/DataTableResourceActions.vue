<template>
  <DropdownMenu v-if="runnableActions.length > 0 || visible">
    <DropdownMenuTrigger>
      <Button :variant="variant" :size="size" :class="cn('px-2 data-[state=open]:bg-muted', $attrs.class || '')" :icon="EllipsisIcon" :label="label" />
    </DropdownMenuTrigger>
    <DropdownMenuContent :align="align">
      <slot :resource="actions.resource" :runnable-actions="runnableActions" />

      <DataTableActionDropdownMenuItem
        v-for="action in runnableActions"
        :action="action"
        :selection="[actions.resource.key]"
        @event="onEvent"
        @exec="onExecAction"
      />
    </DropdownMenuContent>
  </DropdownMenu>

  <DataTableActionDialog
    v-if="actionToRun"
    :control="actionDialog"
    :selection="[actions.resource.key]"
    :action="actionToRun"
  />
</template>

<script setup lang="ts" generic="ResourceKey = string | number, ResourceValue = object">
import { Button, type ButtonVariants } from '@/Components/Button'
import DataTableActionDropdownMenuItem from './DataTableActionDropdownMenuItem.vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/DropdownMenu'
import { cn } from '@/Utils'
import { useToggle } from '@stacktrace/ui'
import { EllipsisIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { type DataTableResourceActionsValue } from '.'
import DataTableActionDialog from './DataTableActionDialog.vue'
import { type ExecutableAction, useActionRunner } from './internal'

const emit = defineEmits() // eslint-disable-line vue/valid-define-emits

const props = withDefaults(defineProps<{
  actions: DataTableResourceActionsValue<ResourceKey, ResourceValue>
  size?: ButtonVariants['size']
  variant?: ButtonVariants['variant']
  visible?: boolean
  align?: 'center' | 'end' | 'start'
  label?: string | undefined
}>(), {
  size: 'sm',
  variant: 'ghost',
  visible: false,
  align: 'end'
})

const runnableActions = computed(() => props.actions.actions.filter(it => it.canRun))

const onEvent = (event: string) => {
  emit(event, [props.actions.resource.key])
}

const { run } = useActionRunner<ResourceKey>()
const actionToRun = ref<ExecutableAction>()
const actionDialog = useToggle()
const onExecAction = (action: ExecutableAction) => {
  if (action.isInline) {
    run(action, [props.actions.resource.key])
    return
  } else {
    actionToRun.value = action
    actionDialog.activate()
  }
}
</script>
