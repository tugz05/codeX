<script setup lang="ts">
import { inject, computed, type Ref } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  value: string
  id?: string
  class?: string
}>(), {
  id: undefined,
  class: ''
})

const radioValue = inject<Ref<string>>('radioValue')
const updateRadioValue = inject<(value: string) => void>('updateRadioValue')

const isChecked = computed(() => radioValue?.value === props.value)

const handleClick = () => {
  updateRadioValue?.(props.value)
}
</script>

<template>
  <button
    :id="props.id"
    type="button"
    role="radio"
    :aria-checked="isChecked"
    :class="cn(
      'aspect-square h-4 w-4 rounded-full border border-primary text-primary ring-offset-background focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      isChecked ? 'border-primary bg-primary' : 'border-input',
      props.class
    )"
    @click="handleClick"
  >
    <span
      v-if="isChecked"
      class="flex items-center justify-center"
    >
      <span class="h-2 w-2 rounded-full bg-primary-foreground" />
    </span>
  </button>
</template>
