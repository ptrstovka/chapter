<template>
  <template v-if="action.canRun">
    <template v-if="action.type === 'Event'">
      <DropdownMenuItem @select="emit('event', action.event)">{{ action.label }}</DropdownMenuItem>
    </template>
    <template v-else-if="action.type === 'Link'">
      <DropdownMenuItem as-child>
        <component :is="action.isExternal ? 'a' : Link" :href="action.url">{{ action.label }}</component>
      </DropdownMenuItem>
    </template>
    <template v-else-if="action.type === 'Executable'">
      <DropdownMenuItem @select="emit('exec', action)">{{ action.label }}</DropdownMenuItem>
    </template>
  </template>
</template>

<script setup lang="ts" generic="ResourceKey = string | number">
import { DropdownMenuItem } from '@/Components/DropdownMenu'
import { Link } from '@inertiajs/vue3'
import { type Action } from './internal'

const emit = defineEmits(['event', 'exec'])

defineProps<{
  action: Action
  selection: Array<ResourceKey>
}>()
</script>
