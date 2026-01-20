<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import { Code } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10">
        <!-- Animated background gradient -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl animate-pulse"></div>
            <div class="absolute right-1/4 bottom-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="group flex flex-col items-center gap-2 font-medium transition-transform duration-300 hover:scale-105">
                        <div class="mb-1 flex h-12 w-12 items-center justify-center rounded-lg border-2 bg-background transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                            <Code class="h-6 w-6 transition-transform duration-300 group-hover:rotate-12" />
                        </div>
                        <span class="text-lg font-bold bg-gradient-to-r from-foreground to-foreground/70 bg-clip-text">codeX</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>
                <div class="rounded-xl border-2 border-border bg-card p-6 shadow-lg transition-all duration-300 hover:shadow-xl">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
