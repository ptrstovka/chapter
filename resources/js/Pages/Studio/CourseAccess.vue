<template>
  <CourseLayout :title="$t('Access')">
    <div class="relative h-[calc(100vh_-_8.5rem)] overflow-y-auto [scrollbar-width:thin]">
      <div class="flex flex-col divide-y">
        <Panel>
          <PanelHeader>
            <PanelTitle>{{ $t('Access mode') }}</PanelTitle>
          </PanelHeader>

          <PanelContent>
            <PanelItem :label="$t('Who can access this course?')" :center="false" class="border-b border-border/50">
              <FormControl :error="modeForm.errors.access_mode">
                <RadioGroup v-model="modeForm.access_mode" class="gap-0 divide-y divide-input rounded-md border border-input shadow-xs">
                  <label
                    v-for="option in accessModeOptions"
                    :key="option.value"
                    class="flex cursor-pointer flex-row gap-3 p-3"
                    :for="`access-mode:${option.value}`"
                  >
                    <RadioGroupItem :id="`access-mode:${option.value}`" :value="option.value" class="mt-0.5" />
                    <span class="min-w-0">
                      <span class="block text-sm font-medium">{{ option.label }}</span>
                      <span class="block text-sm text-muted-foreground">{{ option.description }}</span>
                    </span>
                  </label>
                </RadioGroup>
              </FormControl>
            </PanelItem>
          </PanelContent>

          <PanelFooter class="flex justify-end gap-3">
            <Button
              :processing="modeForm.processing"
              :recently-successful="modeForm.recentlySuccessful"
              :recently-successful-label="$t('Saved.')"
              :label="$t('Save')"
              @click="saveAccessMode"
            />
          </PanelFooter>
        </Panel>

        <Panel>
          <PanelHeader class="flex flex-wrap items-center justify-between gap-3">
            <PanelTitle>{{ $t('Direct access') }}</PanelTitle>
            <Button :icon="UserPlusIcon" :label="$t('Add User')" @click="setUserDialogOpen(true)" />
          </PanelHeader>

          <PanelContent>
            <PanelItem :center="false" class="p-0">
              <FormMessage v-if="revokeUsersForm.hasErrors" class="px-4" :message="Object.values(revokeUsersForm.errors)[0]" />
              <Table class="[&_tr>:first-child]:pl-4 [&_tr>:last-child]:pr-4">
                <TableHeader>
                  <TableRow>
                    <TableHead>{{ $t('User') }}</TableHead>
                    <TableHead>{{ $t('Granted by') }}</TableHead>
                    <TableHead>{{ $t('Granted at') }}</TableHead>
                    <TableHead class="w-24 text-right"><span class="sr-only">{{ $t('Actions') }}</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="grant in directGrants" :key="grant.id">
                    <TableCell>
                      <div class="min-w-0">
                        <div class="truncate font-medium">{{ grant.user.name }}</div>
                        <div class="truncate text-xs text-muted-foreground">{{ grant.user.email }}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div class="min-w-0">
                        <div class="truncate">{{ grant.grantedBy?.name ?? $t('Deleted user') }}</div>
                      </div>
                    </TableCell>
                    <TableCell>{{ formatDate(grant.grantedAt) }}</TableCell>
                    <TableCell class="text-right">
                      <Button
                        variant="ghost-desctructive"
                        :processing="revokeUsersForm.processing && revokeUsersForm.users.includes(grant.user.id)"
                        :label="$t('Remove')"
                        @click="removeDirectGrant(grant)"
                      />
                    </TableCell>
                  </TableRow>
                  <TableEmpty v-if="directGrants.length === 0" :colspan="4">
                    {{ $t('No direct user grants') }}
                  </TableEmpty>
                </TableBody>
              </Table>
            </PanelItem>
          </PanelContent>
        </Panel>

        <Panel>
          <PanelHeader class="flex flex-wrap items-center justify-between gap-3">
            <PanelTitle>{{ $t('Group access') }}</PanelTitle>
            <Button :icon="UsersIcon" :label="$t('Add Group')" @click="setGroupDialogOpen(true)" />
          </PanelHeader>

          <PanelContent>
            <PanelItem :center="false" class="p-0">
              <FormMessage v-if="revokeGroupsForm.hasErrors" class="px-4" :message="Object.values(revokeGroupsForm.errors)[0]" />
              <Table class="[&_tr>:first-child]:pl-4 [&_tr>:last-child]:pr-4">
                <TableHeader>
                  <TableRow>
                    <TableHead>{{ $t('Group') }}</TableHead>
                    <TableHead>{{ $t('Visibility') }}</TableHead>
                    <TableHead class="text-right">{{ $t('Members') }}</TableHead>
                    <TableHead>{{ $t('Granted by') }}</TableHead>
                    <TableHead class="w-24 text-right"><span class="sr-only">{{ $t('Actions') }}</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="grant in groupGrants" :key="grant.id">
                    <TableCell>
                      <div class="min-w-0">
                        <div class="truncate font-medium">{{ grant.group.name }}</div>
                        <div v-if="grant.group.description" class="truncate text-xs text-muted-foreground">{{ grant.group.description }}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge :variant="grant.group.visibility === 'private' ? 'default' : 'positive'">{{ grant.group.visibilityLabel }}</Badge>
                    </TableCell>
                    <TableCell class="text-right tabular-nums">{{ grant.group.usersCount }}</TableCell>
                    <TableCell>
                      <div class="min-w-0">
                        <div class="truncate">{{ grant.grantedBy?.name ?? $t('Deleted user') }}</div>
                      </div>
                    </TableCell>
                    <TableCell class="text-right">
                      <Button
                        variant="ghost-desctructive"
                        :processing="revokeGroupsForm.processing && revokeGroupsForm.groups.includes(grant.group.id)"
                        :label="$t('Remove')"
                        @click="removeGroupGrant(grant)"
                      />
                    </TableCell>
                  </TableRow>
                  <TableEmpty v-if="groupGrants.length === 0" :colspan="5">
                    {{ $t('No group grants') }}
                  </TableEmpty>
                </TableBody>
              </Table>
            </PanelItem>
          </PanelContent>
        </Panel>

        <Panel>
          <PanelHeader>
            <PanelTitle>{{ $t('Pending requests') }}</PanelTitle>
          </PanelHeader>

          <PanelContent>
            <PanelItem :center="false" class="p-0">
              <Table class="[&_tr>:first-child]:pl-4 [&_tr>:last-child]:pr-4">
                <TableHeader>
                  <TableRow>
                    <TableHead>{{ $t('User') }}</TableHead>
                    <TableHead>{{ $t('Requested') }}</TableHead>
                    <TableHead class="w-40 text-right"><span class="sr-only">{{ $t('Actions') }}</span></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="request in pendingRequests" :key="request.id">
                    <TableCell>
                      <div class="min-w-0">
                        <div class="truncate font-medium">{{ request.user.name }}</div>
                        <div class="truncate text-xs text-muted-foreground">{{ request.user.email }}</div>
                      </div>
                    </TableCell>
                    <TableCell>{{ formatDate(request.requestedAt) }}</TableCell>
                    <TableCell>
                      <div class="flex justify-end gap-2">
                        <Button
                          variant="positive"
                          :processing="resolveRequestForm.processing && resolvingRequestId === request.id && resolvingRequestAction === 'approve'"
                          :label="$t('Approve')"
                          @click="approveRequest(request)"
                        />
                        <Button
                          variant="ghost-desctructive"
                          :processing="resolveRequestForm.processing && resolvingRequestId === request.id && resolvingRequestAction === 'deny'"
                          :label="$t('Deny')"
                          @click="denyRequest(request)"
                        />
                      </div>
                    </TableCell>
                  </TableRow>
                  <TableEmpty v-if="pendingRequests.length === 0" :colspan="3">
                    {{ $t('No pending access requests') }}
                  </TableEmpty>
                </TableBody>
              </Table>
            </PanelItem>
          </PanelContent>
        </Panel>
      </div>
    </div>

    <Dialog :open="userDialogOpen" @update:open="setUserDialogOpen">
      <DialogContent class="flex max-h-[calc(100dvh-2rem)] flex-col sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('Add Users') }}</DialogTitle>
          <DialogDescription>
            {{ userDirectory.searchable ? $t('Search by name or email to grant course access.') : $t('Enter an exact email address to grant course access.') }}
          </DialogDescription>
        </DialogHeader>

        <div class="relative">
          <component :is="userSearchProcessing ? Spinner : SearchIcon" class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
          <Input
            v-model="userSearch"
            class="pl-9"
            :placeholder="userDirectory.searchable ? $t('Search users…') : $t('Enter exact email…')"
            :aria-label="userDirectory.searchable ? $t('Search users…') : $t('Enter exact email…')"
            :maxlength="191"
            :aria-busy="userSearchProcessing"
          />
        </div>

        <div class="min-h-40 max-h-72 overflow-y-auto rounded-md border border-border" :aria-busy="userSearchProcessing">
          <div v-if="userSearchProcessing && userSearchResults.length === 0" class="flex h-40 items-center justify-center gap-2 text-sm text-muted-foreground" role="status">
            <Spinner class="size-4" /> {{ $t('Loading users…') }}
          </div>
          <div v-else-if="userLookupMessage" class="flex h-40 items-center justify-center p-4 text-center text-sm text-muted-foreground" role="status">
            {{ userLookupMessage }}
          </div>
          <div v-else-if="selectableUserSearchResults.length === 0" class="flex h-40 items-center justify-center p-4 text-center text-sm text-muted-foreground">
            {{ hasUserSearched ? $t('No users found') : (userDirectory.searchable ? $t('Search users…') : $t('Enter exact email…')) }}
          </div>
          <div v-else class="divide-y divide-border/50">
            <label
              v-for="user in selectableUserSearchResults"
              :key="user.id"
              class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-accent/50"
              :class="{ 'bg-accent/50': isUserSelected(user) }"
            >
              <Checkbox :model-value="isUserSelected(user)" @update:model-value="toggleUser(user)" />
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium">{{ user.name }}</span>
                <span class="block truncate text-sm text-muted-foreground">{{ user.email }}</span>
              </span>
            </label>
          </div>
        </div>

        <FormMessage v-if="grantUsersForm.hasErrors" :message="Object.values(grantUsersForm.errors)[0]" />

        <DialogFooter class="items-center gap-3">
          <span class="text-sm text-muted-foreground sm:mr-auto" aria-live="polite">{{ $t('Selected users: :count', { count: `${grantUsersForm.users.length}` }) }}</span>
          <Button variant="outline" :label="$t('Cancel')" @click="setUserDialogOpen(false)" />
          <Button :icon="UserPlusIcon" :processing="grantUsersForm.processing" :label="$t('Grant Access')" @click="grantSelectedUsers" />
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="groupDialogOpen" @update:open="setGroupDialogOpen">
      <DialogContent class="flex max-h-[calc(100dvh-2rem)] flex-col sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('Add Groups') }}</DialogTitle>
          <DialogDescription>{{ $t('Search for groups to grant their members course access.') }}</DialogDescription>
        </DialogHeader>

        <div class="relative">
          <SearchIcon class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
          <Input v-model="groupSearch" class="pl-9" :placeholder="$t('Search groups…')" :aria-label="$t('Search groups…')" />
        </div>

        <div class="min-h-40 max-h-72 overflow-y-auto rounded-md border border-border">
          <div v-if="filteredAvailableGroups.length === 0" class="flex h-40 items-center justify-center p-4 text-center text-sm text-muted-foreground">
            {{ groupSearch ? $t('No groups found') : $t('No groups available') }}
          </div>
          <div v-else class="divide-y divide-border/50">
            <label
              v-for="group in filteredAvailableGroups"
              :key="group.id"
              class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-accent/50"
              :class="{ 'bg-accent/50': isGroupSelected(group) }"
            >
              <Checkbox :model-value="isGroupSelected(group)" @update:model-value="toggleGroup(group)" />
              <span class="min-w-0 flex-1">
                <span class="flex min-w-0 flex-wrap items-center gap-2">
                  <span class="truncate text-sm font-medium">{{ group.name }}</span>
                  <Badge :variant="group.visibility === 'private' ? 'default' : 'positive'">{{ group.visibilityLabel }}</Badge>
                </span>
                <span class="block text-sm text-muted-foreground">{{ $t('Members (:count)', { count: `${group.usersCount}` }) }}</span>
              </span>
            </label>
          </div>
        </div>

        <FormMessage v-if="grantGroupsForm.hasErrors" :message="Object.values(grantGroupsForm.errors)[0]" />

        <DialogFooter class="items-center gap-3">
          <span class="text-sm text-muted-foreground sm:mr-auto" aria-live="polite">{{ $t('Selected groups: :count', { count: `${grantGroupsForm.groups.length}` }) }}</span>
          <Button variant="outline" :label="$t('Cancel')" @click="setGroupDialogOpen(false)" />
          <Button :icon="UsersIcon" :processing="grantGroupsForm.processing" :label="$t('Grant Access')" @click="grantSelectedGroups" />
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </CourseLayout>
</template>

<script setup lang="ts">
import { Badge } from "@/Components/Badge";
import { Button } from "@/Components/Button";
import { Checkbox } from "@/Components/Checkbox";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/Dialog";
import { Spinner } from "@/Components/Spinner";
import { FormControl, FormMessage } from "@/Components/Form";
import { Input } from "@/Components/Input";
import { Panel, PanelContent, PanelFooter, PanelHeader, PanelItem, PanelTitle } from "@/Components/Panel";
import { RadioGroup, RadioGroupItem } from "@/Components/RadioGroup";
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from "@/Components/Table";
import { useForm } from "@inertiajs/vue3";
import { watchDebounced } from "@vueuse/core";
import { SearchIcon, UserPlusIcon, UsersIcon } from "lucide-vue-next";
import { trans } from "laravel-vue-i18n";
import { computed, onBeforeUnmount, ref, watch } from "vue";
import CourseLayout from "./Layouts/CourseLayout.vue";

type CourseAccessMode = 'public' | 'request-only' | 'private'
type AccessGroupVisibility = 'public' | 'private'

interface AccessModeOption {
  label: string
  value: CourseAccessMode
  description: string
}

interface UserOption {
  id: number
  name: string
  email: string
}

interface GroupOption {
  id: number
  name: string
  description: string | null
  visibility: AccessGroupVisibility
  visibilityLabel: string
  usersCount: number
}

interface DirectGrant {
  id: number
  user: UserOption
  grantedBy: UserOption | null
  grantedAt: string | null
}

interface GroupGrant {
  id: number
  group: GroupOption
  grantedBy: UserOption | null
  grantedAt: string | null
}

interface PendingRequest {
  id: number
  user: UserOption
  requestedAt: string | null
}

const props = defineProps<{
  id: string
  accessMode: CourseAccessMode
  accessModeOptions: Array<AccessModeOption>
  directGrants: Array<DirectGrant>
  groupGrants: Array<GroupGrant>
  availableGroups: Array<GroupOption>
  pendingRequests: Array<PendingRequest>
  userDirectory: {
    searchable: boolean
    directorySearchEnabled: boolean
  }
}>()

const modeForm = useForm(() => ({
  access_mode: props.accessMode,
}))

const saveAccessMode = () => {
  if (modeForm.processing) {
    return
  }

  modeForm.patch(route('studio.courses.access.update', props.id), {
    preserveScroll: true,
  })
}

const directGrantUserIds = computed(() => new Set(props.directGrants.map(grant => grant.user.id)))

const userDialogOpen = ref(false)
const userSearch = ref('')
const userSearchProcessing = ref(false)
const userSearchResults = ref<Array<UserOption>>([])
const userLookupMessage = ref('')
const hasUserSearched = ref(false)
let userLookupController: AbortController | null = null
let userLookupSequence = 0

const grantUsersForm = useForm(() => ({
  users: [] as Array<number>,
  email: '',
}))

const revokeUsersForm = useForm(() => ({
  users: [] as Array<number>,
}))

const cancelUserLookup = () => {
  ++userLookupSequence
  userLookupController?.abort()
  userLookupController = null
  userSearchProcessing.value = false
}

const setUserDialogOpen = (open: boolean) => {
  if (!grantUsersForm.processing) {
    userDialogOpen.value = open
  }
}

watch(userDialogOpen, (open) => {
  if (!open) {
    cancelUserLookup()
    userSearch.value = ''
    userSearchResults.value = []
    userLookupMessage.value = ''
    hasUserSearched.value = false
    grantUsersForm.resetAndClearErrors()
  }
})

watch(userSearch, () => {
  cancelUserLookup()
  userLookupMessage.value = ''

  if (!userSearch.value.trim() || !props.userDirectory.searchable) {
    userSearchResults.value = []
    hasUserSearched.value = false
  }

  if (!props.userDirectory.searchable) {
    grantUsersForm.resetAndClearErrors()
  }
}, { flush: 'sync' })

watchDebounced(userSearch, () => searchUsers(), { debounce: 250 })

onBeforeUnmount(cancelUserLookup)

const isUserSelected = (user: UserOption) => grantUsersForm.users.includes(user.id)

const canSelectUser = (user: UserOption) => !directGrantUserIds.value.has(user.id)

const selectableUserSearchResults = computed(() => userSearchResults.value.filter(canSelectUser))

const toggleUser = (user: UserOption) => {
  grantUsersForm.clearErrors()

  if (!canSelectUser(user)) {
    userLookupMessage.value = trans('User already has direct access.')

    return
  }

  if (isUserSelected(user)) {
    removeSelectedUser(user)

    return
  }

  if (props.userDirectory.searchable) {
    grantUsersForm.users = [...grantUsersForm.users, user.id]
  } else {
    grantUsersForm.users = [user.id]
    grantUsersForm.email = user.email
  }
}

const removeSelectedUser = (user: UserOption) => {
  grantUsersForm.users = grantUsersForm.users.filter(userId => userId !== user.id)
  if (!props.userDirectory.searchable) {
    grantUsersForm.email = ''
  }
  grantUsersForm.clearErrors()
}

const searchUsers = async () => {
  const query = userSearch.value.trim()

  if (!userDialogOpen.value || !query || userSearchProcessing.value) {
    return
  }

  if (!props.userDirectory.searchable && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(query)) {
    return
  }

  userLookupMessage.value = ''
  hasUserSearched.value = true
  userSearchProcessing.value = true
  const sequence = ++userLookupSequence
  const controller = new AbortController()
  userLookupController = controller

  try {
    const response = await fetch(route('studio.users.lookup', { _query: { query } }), {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
      },
    })

    const payload = await response.json()

    if (sequence !== userLookupSequence) {
      return
    }

    if (!response.ok) {
      userLookupMessage.value = payload.message || trans('No users found')
      userSearchResults.value = []

      return
    }

    const users: Array<UserOption> = Array.isArray(payload.data) ? payload.data : (payload.data ? [payload.data] : [])
    userSearchResults.value = users

    if (!props.userDirectory.searchable && users[0]) {
      if (canSelectUser(users[0])) {
        grantUsersForm.users = [users[0].id]
        grantUsersForm.email = users[0].email
        grantUsersForm.clearErrors()
      } else {
        userLookupMessage.value = trans('User already has direct access.')
      }
    }
  } catch {
    if (sequence === userLookupSequence) {
      userLookupMessage.value = trans('Oops, something went wrong…')
    }
  } finally {
    if (sequence === userLookupSequence) {
      userSearchProcessing.value = false
      userLookupController = null
    }
  }
}

const grantSelectedUsers = () => {
  if (grantUsersForm.processing) {
    return
  }

  grantUsersForm.post(route('studio.courses.access.users.store', props.id), {
    preserveScroll: true,
    only: ['directGrants'],
    onSuccess: () => {
      userDialogOpen.value = false
    },
  })
}

const removeDirectGrant = (grant: DirectGrant) => {
  if (revokeUsersForm.processing) {
    return
  }

  revokeUsersForm.users = [grant.user.id]
  revokeUsersForm.delete(route('studio.courses.access.users.destroy', props.id), {
    preserveScroll: true,
  })
}

const groupDialogOpen = ref(false)
const groupSearch = ref('')

const grantGroupsForm = useForm(() => ({
  groups: [] as Array<number>,
}))

const revokeGroupsForm = useForm(() => ({
  groups: [] as Array<number>,
}))

const setGroupDialogOpen = (open: boolean) => {
  if (!grantGroupsForm.processing) {
    groupDialogOpen.value = open
  }
}

watch(groupDialogOpen, (open) => {
  if (!open) {
    groupSearch.value = ''
    grantGroupsForm.resetAndClearErrors()
  }
})

const filteredAvailableGroups = computed(() => {
  const search = groupSearch.value.trim().toLowerCase()

  if (!search) {
    return props.availableGroups
  }

  return props.availableGroups.filter(group => `${group.name} ${group.description || ''}`.toLowerCase().includes(search))
})

const isGroupSelected = (group: GroupOption) => grantGroupsForm.groups.includes(group.id)

const toggleGroup = (group: GroupOption) => {
  grantGroupsForm.clearErrors()

  if (isGroupSelected(group)) {
    grantGroupsForm.groups = grantGroupsForm.groups.filter(groupId => groupId !== group.id)

    return
  }

  grantGroupsForm.groups = [...grantGroupsForm.groups, group.id]
}

const grantSelectedGroups = () => {
  if (grantGroupsForm.processing) {
    return
  }

  grantGroupsForm.post(route('studio.courses.access.groups.store', props.id), {
    preserveScroll: true,
    only: ['groupGrants', 'availableGroups'],
    onSuccess: () => {
      groupDialogOpen.value = false
    },
  })
}

const removeGroupGrant = (grant: GroupGrant) => {
  if (revokeGroupsForm.processing) {
    return
  }

  revokeGroupsForm.groups = [grant.group.id]
  revokeGroupsForm.delete(route('studio.courses.access.groups.destroy', props.id), {
    preserveScroll: true,
  })
}

const resolveRequestForm = useForm({})
const resolvingRequestId = ref<number | null>(null)
const resolvingRequestAction = ref<'approve' | 'deny' | null>(null)

const approveRequest = (request: PendingRequest) => {
  if (resolveRequestForm.processing) {
    return
  }

  resolvingRequestId.value = request.id
  resolvingRequestAction.value = 'approve'
  resolveRequestForm.post(route('studio.courses.access.requests.approve', [props.id, request.id]), {
    preserveScroll: true,
    onFinish: () => {
      resolvingRequestId.value = null
      resolvingRequestAction.value = null
    },
  })
}

const denyRequest = (request: PendingRequest) => {
  if (resolveRequestForm.processing) {
    return
  }

  resolvingRequestId.value = request.id
  resolvingRequestAction.value = 'deny'
  resolveRequestForm.post(route('studio.courses.access.requests.deny', [props.id, request.id]), {
    preserveScroll: true,
    onFinish: () => {
      resolvingRequestId.value = null
      resolvingRequestAction.value = null
    },
  })
}

const formatDate = (value: string | null) => {
  if (!value) {
    return ''
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}
</script>
