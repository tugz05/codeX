<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Code, Mail, Lock, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
const code = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('code') || '';
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    code: code.value, // Include code in form data
});

const submit = () => {
    const url = code.value 
        ? route('login', { code: code.value })
        : route('login');
    
    form.post(url, {
        onFinish: () => form.reset('password'),
    });
};

const googleLoginUrl = computed(() => {
    return code.value 
        ? route('google.redirect', { code: code.value })
        : route('google.redirect');
});
</script>

<template>
    <AuthBase title="Welcome back" description="Log in to continue your coding journey">
        <Head title="Log in" />

        <div class="flex flex-col gap-6">
            <!-- Back to Home Button -->
            <Link :href="route('home')" class="self-start">
                <Button type="button" variant="ghost" size="sm" class="mb-2 transition-all duration-200 hover:scale-105">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Home
                </Button>
            </Link>
            <!-- Status Message -->
            <div v-if="status" class="rounded-lg border-2 border-green-500/20 bg-green-50 p-3 text-center text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ status }}
            </div>

            <!-- Invite Code Message -->
            <div v-if="code" class="rounded-lg border-2 border-blue-500/20 bg-blue-50 p-3 text-center text-sm font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                <Code class="mx-auto mb-1 h-5 w-5" />
                <p>You're joining a class. Please log in to continue.</p>
            </div>

            <!-- Google Sign In Button -->
            <a :href="googleLoginUrl" class="group">
                <Button type="button" variant="outline" class="w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :tabindex="1">
                    <svg class="mr-2 h-5 w-5 transition-transform duration-300 group-hover:scale-110" viewBox="0 0 24 24">
                        <path
                            fill="currentColor"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="currentColor"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="currentColor"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="currentColor"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Continue with Google
                </Button>
            </a>

            <!-- Divider -->
            <div class="relative flex items-center">
                <div class="flex-1 border-t border-border"></div>
                <span class="px-4 text-sm text-muted-foreground">or continue with email</span>
                <div class="flex-1 border-t border-border"></div>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="email" class="flex items-center gap-2">
                            <Mail class="h-4 w-4" />
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="2"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                            class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="flex items-center gap-2">
                                <Lock class="h-4 w-4" />
                                Password
                            </Label>
                            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm transition-colors duration-200 hover:text-primary" :tabindex="6">
                                Forgot password?
                            </TextLink>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Enter your password"
                            class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <Label for="remember" class="flex cursor-pointer items-center space-x-3">
                            <Checkbox id="remember" v-model="form.remember" :tabindex="4" />
                            <span class="text-sm">Remember me</span>
                        </Label>
                    </div>

                    <Button type="submit" class="group mt-2 w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :tabindex="5" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span v-else class="flex items-center gap-2">
                            Log in
                            <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                        </span>
                    </Button>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <TextLink :href="route('register')" class="font-medium transition-colors duration-200 hover:text-primary" :tabindex="7">Sign up</TextLink>
                </div>
            </form>
        </div>
    </AuthBase>
</template>
