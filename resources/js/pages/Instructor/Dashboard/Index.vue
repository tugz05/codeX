<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BookOpen, FileText, CheckCircle2, BarChart3, Users, Clock, TrendingUp } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

defineProps<{
    stats: {
        activeClasses: number;
        pendingSubmissions: number;
        averageScore: number;
        totalActivities: number;
    };
}>();
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground mt-1.5">
                        Overview of your classes and activities
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Classes</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.activeClasses }}</div>
                        <p class="text-xs text-muted-foreground">Total active classes</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Submissions</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pendingSubmissions }}</div>
                        <p class="text-xs text-muted-foreground">Awaiting review</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Average Score</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.averageScore }}%</div>
                        <p class="text-xs text-muted-foreground">Overall performance</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Activities</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalActivities }}</div>
                        <p class="text-xs text-muted-foreground">All activities created</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>Common tasks and shortcuts</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button 
                            variant="outline" 
                            class="w-full justify-start" 
                            @click="router.visit(route('instructor.classlist'))"
                        >
                            <BookOpen class="mr-2 h-4 w-4" />
                            Manage Classes
                        </Button>
                        <Button 
                            variant="outline" 
                            class="w-full justify-start"
                            @click="router.visit(route('instructor.analytics'))"
                        >
                            <BarChart3 class="mr-2 h-4 w-4" />
                            View Analytics
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Overview</CardTitle>
                        <CardDescription>Summary of your teaching activities</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Active Classes</span>
                            <span class="text-sm font-medium">{{ stats.activeClasses }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Pending Reviews</span>
                            <span class="text-sm font-medium">{{ stats.pendingSubmissions }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Average Score</span>
                            <span class="text-sm font-medium">{{ stats.averageScore }}%</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
