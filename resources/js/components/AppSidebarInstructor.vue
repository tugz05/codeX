<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import BottomNavigation from '@/components/BottomNavigation.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BarChart3, NotebookText, SlidersHorizontal, BookOpen, Calendar, Archive } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Analytics',
        href: '/instructor/analytics',
        icon: BarChart3,
    },
    {
        title: 'Class List',
        href: '/instructor/classlist',
        icon: NotebookText,
    },
    {
        title: 'Rubrics',
        href: '/instructor/criteria',
        icon: SlidersHorizontal,
    },
    {
        title: 'Calendar',
        href: '/instructor/calendar',
        icon: Calendar,
    },
    {
        title: 'Archived Classes',
        href: route('instructor.archived-classes.index'),
        icon: Archive,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="hidden md:flex">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('instructor.classlist')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <BottomNavigation :items="mainNavItems" />
    <slot />
</template>
