<template>
  <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10 relative">
    <div class="flex w-full max-w-md flex-col gap-6">
      <Link :href="route('home')" class="flex items-center gap-2 self-center font-medium">
        <Logo class="mr-2 h-6 w-6" />

        {{ $page.props.app.name }}
      </Link>

      <div class="flex flex-col gap-6">
        <Card class="rounded-xl">
          <CardHeader class="px-10 pt-8 pb-0 text-center">
            <CardTitle class="text-xl">{{ title }}</CardTitle>
            <CardDescription>
              {{ description }}
            </CardDescription>
          </CardHeader>
          <CardContent class="px-10 py-8">
            <slot/>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { Logo } from "@/Components/Logo";
import { Link } from "@inertiajs/vue3";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/Card';

defineProps<{
  title?: string;
  description?: string;
}>();
</script>
