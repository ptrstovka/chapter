<template>
  <div class="container relative h-[100vh] flex-col items-center justify-center flex md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">
    <div class="absolute left-4 top-4 lg:hidden md:top-8">
      <Link href="/" class="inline-flex items-center text-lg font-medium">
        <Logo class="mr-2 h-6 w-6" />

        Acme Inc
      </Link>
    </div>

    <div class="relative hidden h-full flex-col bg-muted p-10 text-white dark:border-r lg:flex">
      <div class="absolute inset-0 bg-zinc-900" />

      <div class="relative z-20">
        <Link href="/" class="inline-flex items-center text-lg font-medium">
          <Logo class="mr-2 h-6 w-6" />

          Acme Inc
        </Link>
      </div>

      <div class="relative z-20 mt-auto">
        <!-- ... -->
      </div>
    </div>

    <div class="lg:p-8 w-full">
      <slot />
    </div>
  </div>
</template>
<script setup lang="ts">
import { Logo } from '@/Components/Logo'
import { Link } from '@inertiajs/vue3'
</script>
