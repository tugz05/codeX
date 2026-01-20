<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Simple <> mark to match landing logo -->
        <g
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <polyline points="16,4 8,12 16,20" />
            <polyline points="24,4 32,12 24,20" />
        </g>
    </svg>
</template>
