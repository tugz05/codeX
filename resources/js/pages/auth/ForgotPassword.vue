<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, ArrowRight, ArrowLeft } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout title="Reset your password" description="Enter your email address and we'll send you a link to reset your password">
        <Head title="Forgot password" />

        <div class="flex flex-col gap-6">
            <!-- Status Message -->
            <div v-if="status" class="rounded-lg border-2 border-green-500/20 bg-green-50 p-4 text-center text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ status }}
            </div>

            <!-- Reset Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="email" class="flex items-center gap-2">
                        <Mail class="h-4 w-4" />
                        Email address
                    </Label>
                    <Input 
                        id="email" 
                        type="email" 
                        name="email" 
                        autocomplete="email" 
                        v-model="form.email" 
                        autofocus 
                        placeholder="email@example.com" 
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <Button type="submit" class="group w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-else class="flex items-center gap-2">
                        Send reset link
                        <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                    </span>
                </Button>
            </form>

            <div class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
                <ArrowLeft class="h-4 w-4" />
                <span>Remember your password?</span>
                <TextLink :href="route('login')" class="font-medium transition-colors duration-200 hover:text-primary">Return to login</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
