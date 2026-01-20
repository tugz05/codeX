<script setup lang="ts">
import { provide, ref, watch, type Ref } from 'vue'

const props = withDefaults(defineProps<{
  modelValue?: string
  defaultValue?: string
  class?: string
}>(), {
  modelValue: undefined,
  defaultValue: '',
  class: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const activeTab = ref(props.modelValue || props.defaultValue || '')

watch(() => props.modelValue, (newVal) => {
  if (newVal !== undefined) {
    activeTab.value = newVal
  }
})

const setActiveTab = (value: string) => {
  activeTab.value = value
  emit('update:modelValue', value)
}

provide<Ref<string>>('activeTab', activeTab)
provide<(value: string) => void>('setActiveTab', setActiveTab)
</script>

<template>
  <div :class="props.class">
    <slot />
  </div>
</template>
