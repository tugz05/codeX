<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, CheckCircle2, ArrowRight } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Verify your email" description="We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.">
        <Head title="Email verification" />

        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-center rounded-lg border-2 border-primary/20 bg-primary/5 p-4">
                <Mail class="h-8 w-8 text-primary" />
            </div>

            <div v-if="status === 'verification-link-sent'" class="rounded-lg border-2 border-green-500/20 bg-green-50 p-4 text-center text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                <div class="mb-2 flex items-center justify-center gap-2">
                    <CheckCircle2 class="h-5 w-5" />
                    <span>Verification link sent!</span>
                </div>
                <p class="text-xs">A new verification link has been sent to your email address.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Button type="submit" class="group w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-else class="flex items-center gap-2">
                        Resend verification email
                        <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                    </span>
                </Button>

                <div class="text-center">
                    <TextLink :href="route('logout')" method="post" as="button" class="text-sm text-muted-foreground transition-colors duration-200 hover:text-primary">
                        Log out
                    </TextLink>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
