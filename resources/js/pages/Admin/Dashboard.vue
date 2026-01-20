<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutAdmin.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, GraduationCap, BookOpen, Calendar, School } from 'lucide-vue-next';

defineProps<{
    stats: {
        total_users: number;
        total_students: number;
        total_instructors: number;
        total_classes: number;
        total_academic_years: number;
    };
}>();
</script>

<template>
    <Head title="Admin Dashboard" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
                    <p class="text-muted-foreground mt-1.5">
                        Manage users and academic years
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                        <p class="text-xs text-muted-foreground">All registered users</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Students</CardTitle>
                        <GraduationCap class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_students }}</div>
                        <p class="text-xs text-muted-foreground">Total students</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Instructors</CardTitle>
                        <School class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_instructors }}</div>
                        <p class="text-xs text-muted-foreground">Total instructors</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Classes</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_classes }}</div>
                        <p class="text-xs text-muted-foreground">Active classes</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Academic Years</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_academic_years }}</div>
                        <p class="text-xs text-muted-foreground">Configured academic years</p>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
