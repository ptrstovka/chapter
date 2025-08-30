<template>
  <StudioLayout :title="$t('Profile')">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
        <PlaceholderPattern/>
      </div>
    </div>
  </StudioLayout>
</template>

<script setup lang="ts">
import { PlaceholderPattern } from "@/Components/Skeleton";
import { StudioLayout } from '@/Layouts'
</script>
