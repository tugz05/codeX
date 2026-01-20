<script setup lang="ts">
import { inject, computed, type Ref } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  value: string
  class?: string
}>(), {
  class: ''
})

const activeTab = inject<Ref<string>>('activeTab')
const setActiveTab = inject<(value: string) => void>('setActiveTab')

const isActive = computed(() => activeTab?.value === props.value)

const handleClick = () => {
  setActiveTab?.(props.value)
}
</script>

<template>
  <button
    :class="cn(
      'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      isActive
        ? 'bg-background text-foreground shadow-sm'
        : 'text-muted-foreground hover:bg-background/50',
      props.class
    )"
    :aria-selected="isActive"
    role="tab"
    @click="handleClick"
  >
    <slot />
  </button>
</template>
