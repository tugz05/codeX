<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import BottomNavigation from '@/components/BottomNavigation.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, router } from '@inertiajs/vue3';
import { HomeIcon, LayoutGrid, NotebookText, ClipboardList, GraduationCap, LayoutDashboard, BookOpen, Calendar, Archive, UserCheck } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed, ref } from 'vue';

// Get the current class ID from the URL
const getCurrentClassId = () => {
    const matches = window.location.pathname.match(/\/student\/classlist\/([^\/]+)/);
    return matches ? matches[1] : null;
};

const generateNavItems = (classId: string | null) => [
    {
        title: 'Dashboard',
        href: route('student.dashboard'),
        icon: LayoutDashboard,
    },
    {
        title: 'My Classes',
        href: route('student.classlist'),
        icon: HomeIcon,
    },
    {
        title: 'My Grades',
        href: route('student.grades.index'),
        icon: BookOpen,
    },
    {
        title: 'Calendar',
        href: route('student.calendar.index'),
        icon: Calendar,
    },
    {
        title: 'Archived Classes',
        href: route('student.archived-classes.index'),
        icon: Archive,
    },
    ...(classId ? [
        {
            title: 'Class Activities',
            href: route('student.activities.index', classId),
            icon: NotebookText,
        },
        {
            title: 'Quizzes',
            href: route('student.quizzes.index', classId),
            icon: ClipboardList,
        },
        {
            title: 'Examinations',
            href: route('student.examinations.index', classId),
            icon: GraduationCap,
        },
        {
            title: 'My Submissions',
            href: route('student.submissions.index', classId),
            icon: LayoutGrid,
        },
        {
            title: 'Attendance',
            href: route('student.attendance.index', classId),
            icon: UserCheck,
        }
    ] : [])
];

const mainNavItems = ref(generateNavItems(getCurrentClassId()));

// Watch for page changes to update the classId
router.on('navigate', () => {
    const newClassId = getCurrentClassId();
    if (newClassId) {
        // Update the navigation items with the new class ID
        mainNavItems.value = generateNavItems(newClassId);
    }
});

// Base nav items for bottom navigation (always visible items, excluding class-specific ones)
const baseNavItems = computed(() => [
    {
        title: 'Dashboard',
        href: route('student.dashboard'),
        icon: LayoutDashboard,
    },
    {
        title: 'My Classes',
        href: route('student.classlist'),
        icon: HomeIcon,
    },
    {
        title: 'My Grades',
        href: route('student.grades.index'),
        icon: BookOpen,
    },
    {
        title: 'Calendar',
        href: route('student.calendar.index'),
        icon: Calendar,
    },
    {
        title: 'Archived',
        href: route('student.archived-classes.index'),
        icon: Archive,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="hidden md:flex">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('student.classlist')">
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
    <BottomNavigation :items="baseNavItems" />
    <slot />
</template>
