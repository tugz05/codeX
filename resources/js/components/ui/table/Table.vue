<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  class?: string;
}>();

const classes = computed(() => {
  return `w-full caption-bottom text-sm ${props.class || ''}`;
});
</script>

<template>
  <table :class="classes">
    <slot />
  </table>
</template>
