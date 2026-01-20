<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  class?: string;
}>();

const classes = computed(() => {
  return `p-4 align-middle [&:has([role=checkbox])]:pr-0 ${props.class || ''}`;
});
</script>

<template>
  <td :class="classes">
    <slot />
  </td>
</template>
