<template>
  <div :class="cn('min-h-screen flex flex-col', $attrs.class || '')">
    <div v-if="header" class="h-16 border-b bg-background">
      <!-- Primary Navigation Menu -->
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex items-center shrink-0">
              <Link :href="route('home')" class="inline-flex flex-row items-center gap-4 font-medium">
                <Logo />

                <span>{{ $page.props.app.name }}</span>
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <NavigationMenu>
                <NavigationMenuList>
                  <NavigationMenuItem v-if="$page.props.app.enableExplorePage">
                    <NavigationMenuLink
                      :href="route('explore')"
                      :class="navigationMenuTriggerStyle()"
                      :active="route().current('explore')"
                    >{{ $t('Explore') }}</NavigationMenuLink>
                  </NavigationMenuItem>

                  <NavigationMenuItem>
                    <NavigationMenuLink
                      :href="route('courses')"
                      :class="navigationMenuTriggerStyle()"
                      :active="route().current('courses')"
                    >{{ $t('Browse Courses') }}</NavigationMenuLink>
                  </NavigationMenuItem>

                  <NavigationMenuItem>
                    <NavigationMenuLink
                      :href="route('mycourses.inprogress')"
                      :class="navigationMenuTriggerStyle()"
                      :active="route().current('mycourses.inprogress') || route().current('mycourses.favorite') || route().current('mycourses.completed')"
                    >{{ $t('My Courses') }}</NavigationMenuLink>
                  </NavigationMenuItem>
                </NavigationMenuList>
              </NavigationMenu>
            </div>
          </div>

          <div class="hidden sm:flex sm:items-center sm:ms-6">
            <Button @click="search.activate" variant="ghost" size="icon">
              <SearchIcon class="size-4" />
            </Button>

            <!-- Settings Dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger>
                <Button variant="ghost" class="inline-flex flex-row items-center gap-2">
                  {{ $page.props.auth.user.name }}
                  <ChevronDownIcon class="w-4 h-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuLink :href="route('profile.edit')"><UserRoundCogIcon /> {{ $t('Profile') }}</DropdownMenuLink>
                <template v-if="$page.props.auth.user.can.accessStudio">
                  <DropdownMenuLink :href="route('studio')"><ClapperboardIcon /> {{ $t('Studio') }}</DropdownMenuLink>
                </template>
                <DropdownMenuSub>
                  <DropdownMenuSubTrigger><SunMoonIcon />{{ $t('Theme') }}</DropdownMenuSubTrigger>
                  <DropdownMenuSubContent>
                    <DropdownMenuCheckboxItem @select="updateAppearance('dark')" :model-value="appearance == 'dark'">{{ $t('Dark') }}</DropdownMenuCheckboxItem>
                    <DropdownMenuCheckboxItem @select="updateAppearance('light')" :model-value="appearance == 'light'">{{ $t('Light') }}</DropdownMenuCheckboxItem>
                    <DropdownMenuCheckboxItem @select="updateAppearance('system')" :model-value="appearance == 'system'">{{ $t('System') }}</DropdownMenuCheckboxItem>
                  </DropdownMenuSubContent>
                </DropdownMenuSub>

                <template v-if="$page.props.auth.user.can.viewAdmin">
                  <DropdownMenuLink :href="route('admin')"><WrenchIcon /> {{ $t('Admin') }}</DropdownMenuLink>
                </template>

                <DropdownMenuLink :href="route('logout')" method="post" as="button"><LogOutIcon /> {{ $t('Log Out') }}</DropdownMenuLink>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <!-- Hamburger -->
          <div class="flex items-center -me-2 sm:hidden">
            <Button variant="ghost" class="px-3" @click="showingNavigationDropdown = !showingNavigationDropdown">
              <XIcon v-if="showingNavigationDropdown" class="w-5 h-5" />
              <MenuIcon v-else class="w-5 h-5" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div
        :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
        class="sm:hidden relative z-10 bg-background"
      >
        <div class="flex flex-col gap-1 px-2 pt-2 pb-3">
          <Link v-if="$page.props.app.enableExplorePage" :href="route('explore')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('explore*') || undefined">
            {{ $t('Explore') }}
          </Link>

          <Link :href="route('courses')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('courses*') || undefined">
            {{ $t('Browse Courses') }}
          </Link>

          <Link :href="route('mycourses.inprogress')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('mycourses.*') || undefined">
            {{ $t('My Courses') }}
          </Link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t">
          <div class="px-4">
            <div class="text-base font-medium text-foreground">
              {{ $page.props.auth.user.name }}
            </div>
            <div class="text-sm font-medium text-muted-foreground">{{ $page.props.auth.user.email }}</div>
          </div>

          <div class="flex flex-col gap-1 px-2 mt-3">
            <Link :href="route('profile.edit')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('profile.edit') || undefined">
              {{ $t('Profile') }}
            </Link>

            <Link v-if="$page.props.auth.user.can.accessStudio" :href="route('studio')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('studio*') || undefined">
              {{ $t('Studio') }}
            </Link>

            <Link v-if="$page.props.auth.user.can.viewAdmin" :href="route('admin')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('studio*') || undefined">
              {{ $t('Admin Panel') }}
            </Link>

            <Link :href="route('logout')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" method="post" as="button">
              {{ $t('Log Out') }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Page Heading -->
    <header class="flex items-center justify-center h-16 border-b bg-background" v-if="$slots.header">
      <div :class="{ 'max-w-7xl mx-auto': !fluid }" class="w-full px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main>
      <slot />
    </main>

    <SearchDialog :control="search" />
  </div>
</template>

<script setup lang="ts">
import { useAppearance } from "@/Composables";
import { useToggle } from '@stacktrace/ui'
import { ref } from 'vue'
import { navigationMenuTriggerStyle } from '@/Components/NavigationMenu'
import { ChevronDownIcon, MenuIcon, XIcon, SearchIcon, UserRoundCogIcon, ClapperboardIcon, LogOutIcon, SunMoonIcon, WrenchIcon } from 'lucide-vue-next'
import { cn } from '@/Utils'
import { Link } from '@inertiajs/vue3'
import { NavigationMenu, NavigationMenuList, NavigationMenuItem, NavigationMenuLink } from '@/Components/NavigationMenu'
import {
  DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuLink,
  DropdownMenuCheckboxItem,
  DropdownMenuSub, DropdownMenuSubTrigger, DropdownMenuSubContent
} from '@/Components/DropdownMenu'
import { Button } from '@/Components/Button'
import { Logo } from '@/Components/Logo'
import { SearchDialog } from '@/Components/Search'

const showingNavigationDropdown = ref(false)

const { appearance, updateAppearance } = useAppearance()

const search = useToggle()

withDefaults(defineProps<{
  header?: boolean
  fluid?: boolean
}>(), {
  header: true,
  fluid: false,
})
</script>
