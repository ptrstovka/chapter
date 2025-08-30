<template>
  <Head :title="title || undefined" />

  <SidebarProvider>
    <Sidebar collapsible="icon" variant="inset">
      <SidebarContent>
        <SidebarNavigation
          :menu="page.props.sidebar"
        />
      </SidebarContent>

      <SidebarFooter>
        <SidebarGroup class="p-0">
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <SidebarMenuButton as-child class="whitespace-nowrap" :tooltip="$t('Return to Platform')">
                  <Link :href="route('home')">
                    <ArrowLeftIcon />

                    {{ $t('Return to Platform') }}
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarFooter>
    </Sidebar>

    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
        <div class="flex items-center gap-2">
          <SidebarTrigger class="-ml-1" />
        </div>

        <BreadcrumbNavigation
          v-if="page.props.breadcrumbs.length > 0"
          :list="page.props.breadcrumbs"
        />
      </header>

      <slot />
    </SidebarInset>
  </SidebarProvider>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { type AppPageProps } from '@/Types'
import { type Menu } from '@stacktrace/ui'
import {
  Sidebar, SidebarContent, SidebarNavigation, SidebarProvider, SidebarInset, SidebarTrigger,
  SidebarFooter, SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuItem, SidebarMenuButton,
} from '@/Components/Sidebar'
import { BreadcrumbNavigation } from '@/Components/Breadcrumb'
import { ArrowLeftIcon } from 'lucide-vue-next'

defineProps<{
  title?: string | null | undefined
}>()

const page = usePage<AppPageProps & {
  sidebar: Menu
  breadcrumbs: Menu
}>()
</script>
