<script setup lang="ts">
import { inject, type Ref } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  class?: string
}>(), {
  class: ''
})

const activeTab = inject<Ref<string>>('activeTab')
</script>

<template>
  <div
    :class="cn(
      'inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground',
      props.class
    )"
    role="tablist"
  >
    <slot />
  </div>
</template>
