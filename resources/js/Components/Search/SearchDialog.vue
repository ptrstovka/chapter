<template>
  <Dialog :control="control || dialog">
    <DialogContent class="overflow-hidden p-0 shadow-lg">
      <VisuallyHidden>
        <DialogTitle>{{ $t('Search') }}</DialogTitle>
      </VisuallyHidden>

      <ComboboxRoot :ignore-filter="true">
        <div class="flex items-center px-3 mt-1 pb-1">
          <ComboboxInput
            class="border-none focus:ring-0 flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
            :placeholder="$t('Search…')"
            v-model="term"
          />
        </div>

        <ComboboxContent class="p-1 -mt-3" v-if="showContent">
          <div v-if="isError" class="py-6 text-center text-sm">
            {{ $t('Oops, something went wrong…') }}
          </div>
          <div v-else-if="isEmpty" class="py-6 text-center text-sm">
            {{ $t('No courses found matching your search criteria.') }}
          </div>

          <ComboboxGroup v-if="result.length > 0">
            <ComboboxLabel class="px-2 py-1.5 text-xs font-medium text-muted-foreground">{{ $t('Courses') }}</ComboboxLabel>
            <ComboboxItem
              v-for="item in result"
              :value="item"
              @select="onSelected(item)"
              class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
            >
              <div class="flex flex-col w-full">
                {{ item.title }}
                <div class="inline-flex">
                  <span class="text-xs text-muted-foreground">{{ item.author }}</span>
                  <template v-if="item.duration">
                    <DotIcon class="size-4 mt-px text-muted-foreground" />
                    <span class="text-xs text-muted-foreground">{{ item.duration }}</span>
                  </template>
                </div>
              </div>
            </ComboboxItem>
          </ComboboxGroup>
        </ComboboxContent>
      </ComboboxRoot>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { useMagicKeys, watchDebounced } from '@vueuse/core'
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { Dialog, DialogContent, DialogTitle } from '@/Components/Dialog'
import { type Toggle, useToggle } from '@/Composables'
import {
  ComboboxContent,
  ComboboxInput,
  ComboboxGroup,
  ComboboxLabel,
  ComboboxItem,
  ComboboxRoot,
  VisuallyHidden,
} from 'reka-ui'
import { DotIcon } from 'lucide-vue-next'

const props = defineProps<{
  control?: Toggle
}>()

const dialog = useToggle()

interface ResultItem {
  title: string
  url: string
  author: string
  duration: string | null
}

const term = ref<string>()
const result = ref<Array<ResultItem>>([])
const isEmpty = ref(false)
const isError = ref(false)

const showContent = computed(() => result.value.length > 0 || isEmpty.value || isEmpty.value)

const onSelected = (item: ResultItem) => {
  (props.control || dialog).deactivate()
  router.visit(item.url)
}

const clear = () => {
  result.value = []
  isEmpty.value = false
  isError.value = false
}

let pendingSearch: AbortController | undefined = undefined

const stopPendingSearch = () => {
  if (pendingSearch) {
    pendingSearch.abort()
    pendingSearch = undefined
  }
}

const performSearch = async (term: string) => {
  if (term.length == 0) {
    clear()
    stopPendingSearch()
    return
  }

  const controller = new AbortController()
  pendingSearch = controller

  isError.value = false

  try {
    const response = await axios.get<Array<ResultItem>>(route('search', { _query: { term } }), {
      signal: controller.signal,
    })

    result.value = response.data

    isEmpty.value = result.value.length === 0
  } catch (error) {
    if (!axios.isCancel(error)) {
      isError.value = true
    }
  }
}

watchDebounced(term, searchTerm => {
  performSearch(searchTerm || '').then()
}, { debounce: 50 })

const { Meta_K, Ctrl_K } = useMagicKeys({
  passive: false,
  onEventFired(e) {
    if (e.key === 'k' && (e.metaKey || e.ctrlKey))
      e.preventDefault()
  },
})

watch([Meta_K, Ctrl_K], (v) => {
  const dialogToggle = props.control || dialog

  if (v[0] || v[1]) {
    if (dialogToggle.active.value) {
      dialogToggle.deactivate()
    } else {
      dialogToggle.activate()
    }
  }
})
</script>
