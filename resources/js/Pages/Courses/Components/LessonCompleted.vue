<template>
  <div class="bg-background z-50 border rounded-md flex flex-col items-center justify-center">
    <BlurReveal :delay="0" :duration="0.5" class="flex flex-col items-center justify-center gap-4">
      <p class="text-2xl font-medium">{{ next ? $t('Lesson completed!') : $t('Course completed!') }}</p>
      <Button @click="onNextLesson" v-if="next">
        {{ $t('Continue') }} <ArrowRightIcon class="w-4 h-4" />
      </Button>
      <p v-if="auto && next" class="text-sm text-muted-foreground text-center tabular-nums">{{ $t('Next lesson starts in :time…', { time: `${remainingTime}` }) }}</p>
    </BlurReveal>
  </div>
</template>

<script setup lang="ts">
import { useInterval } from '@vueuse/core'
import { BlurReveal } from '@/Components/BlurReveal'
import { Button } from '@/Components/Button'
import { computed, onMounted, watch } from 'vue'
import { ArrowRightIcon } from 'lucide-vue-next'

const emit = defineEmits(['next'])

const props = withDefaults(defineProps<{
  duration?: number
  auto?: boolean
  next?: boolean
}>(), {
  duration: 4,
  auto: false,
  next: true,
})

const { counter, resume, pause } = useInterval(1000, { controls: true, immediate: false })

const remainingTime = computed(() => {
  if (counter.value >= props.duration) {
    return 1
  }

  return props.duration - counter.value
})

onMounted(() => {
  if (props.auto && props.next) {
    resume()
  }
})

const onNextLesson = () => {
  pause()
  emit('next')
}

watch(counter, count => {
  if (count == props.duration) {
    pause()
    emit('next')
  }
})
</script>
