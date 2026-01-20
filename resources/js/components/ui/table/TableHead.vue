<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  class?: string;
}>();

const classes = computed(() => {
  return `h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0 ${props.class || ''}`;
});
</script>

<template>
  <th :class="classes">
    <slot />
  </th>
</template>
