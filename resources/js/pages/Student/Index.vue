<script setup lang="ts">
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue';
import Index from './ClassList/Index.vue';

const props = defineProps<{
  joinedClasses: Array<any>
}>()
</script>

<template>
    <AuthLayoutStudent>
        <Index :joined-classes="props.joinedClasses" />
    </AuthLayoutStudent>
</template>
