<template>
  <AdminLayout :title="pageTitle">
    <div class="flex flex-col divide-y">
      <Panel>
        <PanelHeader>
          <PanelTitle>{{ pageTitle }}</PanelTitle>
        </PanelHeader>

        <PanelContent>
          <PanelItem v-if="group" :label="$t('ID')">
            <code class="flex h-9 items-center">{{ group.id }}</code>
          </PanelItem>

          <PanelItem :label="$t('Name')">
            <FormControl :error="detailsForm.errors.name">
              <Input v-model="detailsForm.name" class="sm:max-w-md" />
            </FormControl>
          </PanelItem>

          <PanelItem :label="$t('Description')" :center="false">
            <FormControl :error="detailsForm.errors.description">
              <Textarea v-model="detailsForm.description" class="sm:max-w-md" />
            </FormControl>
          </PanelItem>

          <PanelItem :label="$t('Visibility')" class="border-b border-border/50" :center="false">
            <FormControl :error="detailsForm.errors.visibility" class="sm:max-w-md">
              <RadioGroup v-model="detailsForm.visibility" class="border rounded-md shadow-xs divide-y divide-input border-input gap-0">
                <div
                  v-for="option in visibilityOptions"
                  :key="option.value"
                  class="flex flex-row p-3 gap-3 w-full"
                >
                  <RadioGroupItem :id="`visibility:${option.value}`" :value="option.value" />
                  <div class="pt-px">
                    <Label :for="`visibility:${option.value}`">{{ option.label }}</Label>
                  </div>
                </div>
              </RadioGroup>
            </FormControl>
          </PanelItem>
        </PanelContent>

        <PanelFooter class="flex justify-end gap-4">
          <Button v-if="group && group.canDelete" @click="destroy" variant="ghost-desctructive" :label="$t('Delete')" />

          <Button :recently-successful="detailsForm.recentlySuccessful" :processing="detailsForm.processing" @click="save" :label="$t(group ? 'Save' : 'Create')" />
        </PanelFooter>
      </Panel>

      <Panel v-if="group && members">
        <PanelHeader class="flex flex-wrap items-center justify-between gap-3">
          <PanelTitle>{{ $t('Members (:count)', { count: `${group.usersCount}` }) }}</PanelTitle>
          <Button :icon="UserPlusIcon" :label="$t('Add Member')" @click="addMembersOpen = true" />
        </PanelHeader>

        <PanelContent>
          <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="relative w-full sm:max-w-sm">
                <SearchIcon class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
                <Input v-model="memberSearch" class="pl-9" :placeholder="$t('Search members…')" :aria-label="$t('Search members…')" :maxlength="191" />
              </div>
              <Button
                v-if="selectedMemberIds.length > 0"
                variant="ghost-desctructive"
                :icon="UserMinusIcon"
                :label="$t('Remove Members')"
                :processing="removeMembersForm.processing && removingMembersInBulk"
                @click="removeMembers(selectedMemberIds, true)"
              />
            </div>

            <FormMessage v-if="removeMembersForm.hasErrors" :message="Object.values(removeMembersForm.errors)[0]" />

            <div class="overflow-hidden rounded-md border border-border" :aria-busy="membersLoading">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-10">
                      <Checkbox
                        v-if="members.data.length > 0"
                        :model-value="allMembersSelected"
                        :indeterminate="selectedMemberIds.length > 0 && !allMembersSelected"
                        :aria-label="$t('Select all members on this page')"
                        @update:model-value="toggleAllMembers"
                      />
                    </TableHead>
                    <TableHead>{{ $t('User') }}</TableHead>
                    <TableHead class="w-24 text-right"><span class="sr-only">{{ $t('Actions') }}</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="user in members.data" :key="user.id">
                    <TableCell>
                      <Checkbox
                        v-model="selectedMemberIds"
                        :value="user.id"
                        :aria-label="$t('Select :name', { name: user.name })"
                      />
                    </TableCell>
                    <TableCell class="max-w-0">
                      <div class="truncate font-medium">{{ user.name }}</div>
                      <div class="truncate text-sm text-muted-foreground">{{ user.email }}</div>
                    </TableCell>
                    <TableCell class="text-right">
                      <Button
                        variant="ghost-desctructive"
                        :label="$t('Remove')"
                        :aria-label="$t('Remove :name', { name: user.name })"
                        :processing="removeMembersForm.processing && !removingMembersInBulk && removeMembersForm.users.includes(user.id)"
                        @click="removeMembers([user.id])"
                      />
                    </TableCell>
                  </TableRow>
                  <TableEmpty v-if="members.data.length === 0" :colspan="3" class="whitespace-normal">
                    <div class="flex flex-col items-center gap-2 text-center">
                      <UsersIcon class="size-6 text-muted-foreground" />
                      <span>{{ memberSearch ? $t('No members found') : $t('No group members') }}</span>
                      <span v-if="!memberSearch" class="text-sm text-muted-foreground">{{ $t('Add members to give them access through this group.') }}</span>
                    </div>
                  </TableEmpty>
                </TableBody>
              </Table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
              <span class="text-sm text-muted-foreground">{{ $t('PaginatorTotal') }}: {{ members.total }}</span>
              <Pagination
                v-if="members.lastPage > 1"
                :page="members.currentPage"
                :total="members.total"
                :items-per-page="members.perPage"
                class="mx-0 w-auto"
              >
                <PaginationContent>
                  <Button variant="ghost" :aria-disabled="members.currentPage === 1" :aria-label="$t('PaginatorPrevious')" @click="loadMembers(members.currentPage - 1)">
                    <ChevronLeftIcon class="size-4" />
                  </Button>
                  <span class="px-2 text-sm tabular-nums">{{ members.currentPage }} {{ $t('of') }} {{ members.lastPage }}</span>
                  <Button variant="ghost" :aria-disabled="members.currentPage === members.lastPage" :aria-label="$t('Next')" @click="loadMembers(members.currentPage + 1)">
                    <ChevronRightIcon class="size-4" />
                  </Button>
                </PaginationContent>
              </Pagination>
            </div>
          </div>
        </PanelContent>
      </Panel>

      <Dialog :open="addMembersOpen" @update:open="setAddMembersOpen">
        <DialogContent class="flex max-h-[calc(100dvh-2rem)] flex-col sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>{{ $t('Add Members') }}</DialogTitle>
            <DialogDescription>{{ $t('Search by name or email and select users to add to this group.') }}</DialogDescription>
          </DialogHeader>

          <div class="relative">
            <component :is="availableLoading ? Spinner : SearchIcon" class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
            <Input
              v-model="availableSearch"
              class="pl-9"
              :placeholder="$t('Search users…')"
              :aria-label="$t('Search users…')"
              :maxlength="191"
              :aria-busy="availableLoading"
            />
          </div>

          <div class="min-h-40 overflow-y-auto rounded-md border border-border" :aria-busy="availableLoading">
            <div v-if="availableLoading && !availableUsers" class="flex h-40 items-center justify-center gap-2 text-sm text-muted-foreground" role="status">
              <Spinner class="size-4" /> {{ $t('Loading users…') }}
            </div>
            <div v-else-if="lookupError" class="flex h-40 flex-col items-center justify-center gap-3 p-4 text-center">
              <FormMessage :message="lookupError" />
              <Button variant="outline" :label="$t('Try again')" @click="loadAvailableUsers()" />
            </div>
            <div v-else-if="availableUsers?.data.length === 0" class="flex h-40 items-center justify-center p-4 text-center text-sm text-muted-foreground">
              {{ availableSearch ? $t('No users found') : $t('No available users') }}
            </div>
            <div v-else class="divide-y divide-border/50">
              <label
                v-for="user in availableUsers?.data || []"
                :key="user.id"
                class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-accent/50"
                :class="{ 'bg-accent/50': isUserSelected(user.id) }"
              >
                <Checkbox :model-value="isUserSelected(user.id)" @update:model-value="toggleUser(user)" />
                <span class="min-w-0">
                  <span class="block truncate text-sm font-medium">{{ user.name }}</span>
                  <span class="block truncate text-sm text-muted-foreground">{{ user.email }}</span>
                </span>
              </label>
            </div>
          </div>

          <Pagination
            v-if="availableUsers && availableUsers.lastPage > 1"
            :page="availableUsers.currentPage"
            :total="availableUsers.total"
            :items-per-page="availableUsers.perPage"
            class="mx-0 w-auto self-end"
          >
            <PaginationContent>
              <Button variant="ghost" :aria-disabled="availableUsers.currentPage === 1" :aria-label="$t('PaginatorPrevious')" @click="loadAvailableUsers(availableUsers.currentPage - 1)">
                <ChevronLeftIcon class="size-4" />
              </Button>
              <span class="px-2 text-sm tabular-nums">{{ availableUsers.currentPage }} {{ $t('of') }} {{ availableUsers.lastPage }}</span>
              <Button variant="ghost" :aria-disabled="availableUsers.currentPage === availableUsers.lastPage" :aria-label="$t('Next')" @click="loadAvailableUsers(availableUsers.currentPage + 1)">
                <ChevronRightIcon class="size-4" />
              </Button>
            </PaginationContent>
          </Pagination>

          <FormMessage v-if="addMembersForm.hasErrors" :message="Object.values(addMembersForm.errors)[0]" />

          <DialogFooter class="items-center gap-3">
            <span class="text-sm text-muted-foreground sm:mr-auto" aria-live="polite">{{ $t('Selected members: :count', { count: `${selectedUsers.length}` }) }}</span>
            <Button variant="outline" :label="$t('Cancel')" @click="setAddMembersOpen(false)" />
            <Button
              :icon="UserPlusIcon"
              :label="$t('Add Members')"
              :processing="addMembersForm.processing"
              @click="addMembers"
            />
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Button } from "@/Components/Button";
import { Checkbox } from "@/Components/Checkbox";
import { useConfirmable } from "@/Components/ConfirmationDialog";
import { FormControl, FormMessage } from "@/Components/Form";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/Dialog";
import { Input } from "@/Components/Input";
import { Label } from "@/Components/Label";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { Pagination, PaginationContent } from "@/Components/Pagination";
import { Spinner } from "@/Components/Spinner";
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from "@/Components/Table";
import { RadioGroup, RadioGroupItem } from "@/Components/RadioGroup";
import { Textarea } from "@/Components/Textarea";
import { useSaveShortcut } from "@/Composables/useKeyboard.ts";
import { AdminLayout } from "@/Layouts";
import type { Paginator } from "@/Types";
import { router, useForm, useHttp } from "@inertiajs/vue3";
import { watchDebounced } from "@vueuse/core";
import { asyncRouter, type SelectOption } from "@stacktrace/ui";
import { ChevronLeftIcon, ChevronRightIcon, SearchIcon, UserMinusIcon, UserPlusIcon, UsersIcon } from "lucide-vue-next";
import { trans } from "laravel-vue-i18n";
import { computed, onBeforeUnmount, ref, watch } from "vue";

type AccessGroupVisibility = 'public' | 'private'

interface Group {
  id: number
  name: string
  description: string | null
  visibility: AccessGroupVisibility
  usersCount: number
  canDelete: boolean
}

interface UserOption {
  id: number
  name: string
  email: string
}

const props = defineProps<{
  group?: Group
  visibilityOptions: Array<SelectOption<AccessGroupVisibility>>
  members?: Paginator<UserOption>
  memberSearch?: string
}>()

const pageTitle = computed(() => props.group ? trans('Group :name', { name: props.group.name }) : trans('New Group'))

const detailsForm = useForm(() => ({
  name: props.group?.name || '',
  description: props.group?.description || '',
  visibility: props.group?.visibility || 'public' as AccessGroupVisibility,
}))

const save = () => {
  if (detailsForm.processing) {
    return
  }

  const method = props.group ? 'patch' : 'post'
  const url = props.group ? route('admin.groups.update', props.group.id) : route('admin.groups.store')

  detailsForm.submit(method, url, {
    preserveScroll: true,
    onSuccess: () => {
      detailsForm.reset()
    }
  })
}

const addMembersForm = useForm(() => ({
  users: [] as Array<number>,
}))

const removeMembersForm = useForm(() => ({
  users: [] as Array<number>,
}))

const memberSearch = ref(props.memberSearch || '')
const membersLoading = ref(false)
const selectedMemberIds = ref<Array<number>>([])
const removingMembersInBulk = ref(false)
let memberVisit = 0

const allMembersSelected = computed(() => !!props.members?.data.length
  && props.members.data.every(user => selectedMemberIds.value.includes(user.id)))

const toggleAllMembers = () => {
  selectedMemberIds.value = allMembersSelected.value ? [] : (props.members?.data.map(user => user.id) || [])
}

const loadMembers = (page = 1) => {
  if (!props.group || page < 1 || page > (props.members?.lastPage || 1)) {
    return
  }

  const visit = ++memberVisit
  router.get(route('admin.groups.edit', props.group.id), {
    members_search: memberSearch.value.trim() || undefined,
    members_page: page,
  }, {
    only: ['members', 'memberSearch', 'group'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => { membersLoading.value = true },
    onFinish: () => {
      if (visit === memberVisit) {
        membersLoading.value = false
      }
    },
  })
}

watchDebounced(memberSearch, () => loadMembers(), { debounce: 250 })
watch(() => props.members, () => {
  selectedMemberIds.value = []
  removeMembersForm.clearErrors()
})

interface AvailableUsers {
  data: Array<UserOption>
  currentPage: number
  lastPage: number
  perPage: number
  total: number
}

const addMembersOpen = ref(false)
const availableSearch = ref('')
const availableUsers = ref<AvailableUsers | null>(null)
const selectedUsers = ref<Array<UserOption>>([])
const availableLoading = ref(false)
const lookupError = ref('')
const lookup = useHttp<{ query: string; page: number }, AvailableUsers>({ query: '', page: 1 })
let lookupSequence = 0

const loadAvailableUsers = async (page = 1) => {
  if (!props.group || !addMembersOpen.value || page < 1 || page > (availableUsers.value?.lastPage || 1)) {
    return
  }

  const sequence = ++lookupSequence
  lookup.cancel()
  lookup.query = availableSearch.value.trim()
  lookup.page = page
  lookup.clearErrors()
  lookupError.value = ''
  availableLoading.value = true

  try {
    const response = await lookup.get(route('admin.groups.users.available', props.group.id))
    if (sequence === lookupSequence) {
      availableUsers.value = response
    }
  } catch {
    if (sequence === lookupSequence) {
      lookupError.value = Object.values(lookup.errors)[0] || trans('Oops, something went wrong…')
    }
  } finally {
    if (sequence === lookupSequence) {
      availableLoading.value = false
    }
  }
}

watch(availableSearch, () => {
  if (addMembersOpen.value) {
    ++lookupSequence
    lookup.cancel()
    availableLoading.value = true
  }
}, { flush: 'sync' })
watchDebounced(availableSearch, () => {
  if (addMembersOpen.value) {
    loadAvailableUsers()
  }
}, { debounce: 250 })

watch(addMembersOpen, (open) => {
  if (open) {
    loadAvailableUsers()
  } else {
    ++lookupSequence
    lookup.cancel()
    availableSearch.value = ''
    availableUsers.value = null
    selectedUsers.value = []
    lookupError.value = ''
    addMembersForm.resetAndClearErrors()
  }
})

onBeforeUnmount(() => {
  ++lookupSequence
  lookup.cancel()
})

const isUserSelected = (id: number) => selectedUsers.value.some(user => user.id === id)
const toggleUser = (user: UserOption) => {
  selectedUsers.value = isUserSelected(user.id)
    ? selectedUsers.value.filter(selected => selected.id !== user.id)
    : [...selectedUsers.value, user]
  addMembersForm.users = selectedUsers.value.map(selected => selected.id)
  addMembersForm.clearErrors()
}

const setAddMembersOpen = (open: boolean) => {
  if (!addMembersForm.processing) {
    addMembersOpen.value = open
  }
}

const addMembers = () => {
  if (!props.group || addMembersForm.processing) {
    return
  }

  addMembersForm.post(route('admin.groups.users.store', props.group.id), {
    preserveScroll: true,
    only: ['members', 'memberSearch', 'group'],
    onSuccess: () => { addMembersOpen.value = false },
  })
}

const removeMembers = (userIds: Array<number>, inBulk = false) => {
  if (!props.group || removeMembersForm.processing || userIds.length === 0) {
    return
  }

  removeMembersForm.users = [...userIds]
  removingMembersInBulk.value = inBulk
  removeMembersForm.delete(route('admin.groups.users.destroy', props.group.id), {
    preserveScroll: true,
    only: ['members', 'memberSearch', 'group'],
    onSuccess: () => { removeMembersForm.reset() },
  })
}

const { confirm } = useConfirmable()
const destroy = () => confirm(
  trans('Are you sure you want to delete this group?'),
  async () => {
    const id = props.group?.id

    if (id) {
      await asyncRouter.delete(route('admin.groups.destroy', id))
    }
  },
  { title: trans('Delete Group'), confirmLabel: trans('Delete'), cancelLabel: trans('Keep'), destructive: true },
)

useSaveShortcut(() => save())
</script>
