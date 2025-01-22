<template>
  <div class="min-h-screen flex flex-col bg-stone-50 dark:bg-background">
    <div class="border-b bg-background">
      <!-- Primary Navigation Menu -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
              <Link :href="route('dashboard')" class="inline-flex flex-row gap-4 items-center font-medium">
                <Logo class="block h-8 w-auto text-foreground" />
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <NavigationMenu>
                <NavigationMenuList>
                  <NavigationMenuItem>
                    <NavigationMenuLink
                      :href="route('dashboard')"
                      :class="navigationMenuTriggerStyle()"
                      :active="route().current('dashboard')"
                    >Dashboard</NavigationMenuLink>
                  </NavigationMenuItem>
                </NavigationMenuList>
              </NavigationMenu>
            </div>
          </div>

          <div class="hidden sm:flex sm:items-center sm:ms-6">
            <!-- Settings Dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger>
                <Button variant="ghost" class="inline-flex flex-row items-center gap-2">
                  {{ $page.props.auth.user.name }}
                  <ChevronDownIcon class="w-4 h-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuLink :href="route('profile.edit')">
                  Profile
                </DropdownMenuLink>
                <DropdownMenuSeparator />
                <DropdownMenuLink :href="route('logout')" method="post" as="button">
                  Log Out
                </DropdownMenuLink>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <!-- Hamburger -->
          <div class="-me-2 flex items-center sm:hidden">
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
        class="sm:hidden"
      >
        <div class="pt-2 pb-3 flex flex-col gap-1 px-2">
          <Link :href="route('dashboard')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('dashboard') || undefined">
            Dashboard
          </Link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t">
          <div class="px-4">
            <div class="font-medium text-base text-foreground">
              {{ $page.props.auth.user.name }}
            </div>
            <div class="font-medium text-sm text-muted-foreground">{{ $page.props.auth.user.email }}</div>
          </div>

          <div class="mt-3 flex flex-col gap-1 px-2">
            <Link :href="route('profile.edit')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" :data-active="route().current('profile.edit') || undefined">
              Profile
            </Link>

            <Link :href="route('logout')" :class="cn(navigationMenuTriggerStyle(), 'w-full justify-start px-2')" method="post" as="button">
              Log Out
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Page Heading -->
    <header class="border-b bg-background" v-if="$slots.header">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { navigationMenuTriggerStyle } from '@/Components/NavigationMenu'
import { ChevronDownIcon, MenuIcon, XIcon } from 'lucide-vue-next'
import { cn } from '@/Utils'
import { Link } from '@inertiajs/vue3'
import { NavigationMenu, NavigationMenuList, NavigationMenuItem, NavigationMenuLink } from '@/Components/NavigationMenu'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuLink, DropdownMenuSeparator } from '@/Components/DropdownMenu'
import { Button } from '@/Components/Button'
import { Logo } from '@/Components/Logo'

const showingNavigationDropdown = ref(false)
</script>
