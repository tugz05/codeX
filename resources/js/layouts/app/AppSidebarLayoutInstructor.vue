<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebarInstructor.vue';
import AppSidebarHeader from '@/components/AppSidebarHeaderInstructor.vue';
import AppFooter from '@/components/AppFooter.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden flex flex-col min-h-screen">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex-1 pb-16 md:pb-0">
                <slot />
            </div>
            <AppFooter />
        </AppContent>
    </AppShell>
</template>
