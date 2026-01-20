<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  class?: string;
}>();

const classes = computed(() => {
  return `border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted ${props.class || ''}`;
});
</script>

<template>
  <tr :class="classes">
    <slot />
  </tr>
</template>
