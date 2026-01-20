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

const value = ref(props.modelValue || props.defaultValue || '')

watch(() => props.modelValue, (newVal) => {
  if (newVal !== undefined) {
    value.value = newVal
  }
})

const updateValue = (newValue: string) => {
  value.value = newValue
  emit('update:modelValue', newValue)
}

provide<Ref<string>>('radioValue', value)
provide<(value: string) => void>('updateRadioValue', updateValue)
</script>

<template>
  <div :class="props.class" role="radiogroup">
    <slot />
  </div>
</template>
