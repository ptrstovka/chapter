<template>
  <Dialog :control="control">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ action.title }}</DialogTitle>
        <DialogDescription>{{ action.description }}</DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button @click="control.deactivate" variant="outline">{{ action.cancelLabel }}</Button>
        <Button
          :processing="isRunning"
          :variant="action.isDestructive ? 'destructive' : 'default'"
          @click="runAction"
        >{{ action.confirmLabel }}</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts" generic="ResourceKey = string | number">
import { Button } from '@/Components/Button'
import { type ExecutableAction, useActionRunner } from './internal'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/Components/Dialog'
import { type Toggle } from '@stacktrace/ui'

const props = defineProps<{
  control: Toggle
  action: ExecutableAction
  selection: Array<ResourceKey>
}>()

const { run, isRunning } = useActionRunner<ResourceKey>()

const runAction = () => {
  run(props.action, props.selection, {
    onSuccess: () => {
      props.control.deactivate()
    }
  })
}
</script>
