<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    items: NavItem[];
    label?: string;
}>(), {
    label: 'Menu',
});

const page = usePage();

const isActive = computed(() => (href: string) => {
    // Supports nested routes: /instructor/classlist/* should keep Class List active
    if (!href) return false;
    return page.url === href || page.url.startsWith(href + '/');
});
</script>

<template>
    <SidebarGroup class="px-2 py-1">
        <SidebarGroupLabel class="px-2 text-[11px] uppercase tracking-wide">{{ props.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item.href)"
                    :tooltip="item.title"
                    class="rounded-xl px-3 py-2.5 text-[13px] [&>svg]:size-[18px]"
                >
                    <Link :href="item.href" class="flex items-center gap-2">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
