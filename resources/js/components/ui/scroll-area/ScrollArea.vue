<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  class?: string;
  orientation?: 'vertical' | 'horizontal';
}

const props = withDefaults(defineProps<Props>(), {
  orientation: 'vertical',
});

const classes = computed(() => {
  const base = 'relative overflow-hidden';
  const orientationClass = props.orientation === 'vertical' ? 'overflow-y-auto' : 'overflow-x-auto';
  return `${base} ${orientationClass} ${props.class || ''}`;
});
</script>

<template>
  <div :class="classes">
    <slot />
  </div>
</template>
