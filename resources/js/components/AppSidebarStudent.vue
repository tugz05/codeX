<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, router } from '@inertiajs/vue3';
import { HomeIcon, LayoutGrid, NotebookText, ClipboardList, GraduationCap, LayoutDashboard, BookOpen, Calendar, Archive } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed, ref } from 'vue';

// Get the current class ID from the URL
const getCurrentClassId = () => {
    const matches = window.location.pathname.match(/\/student\/classlist\/(\d+)/);
    return matches ? matches[1] : null;
};

// Watch for page changes to update the classId
router.on('navigate', (event) => {
    const newClassId = getCurrentClassId();
    if (newClassId) {
        // Update the navigation items with the new class ID
        mainNavItems.value = generateNavItems(newClassId);
    }
});

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
        }
    ] : [])
];

const mainNavItems = ref(generateNavItems(getCurrentClassId()));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
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
    <slot />
</template>
