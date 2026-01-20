<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, Lock, ArrowRight } from 'lucide-vue-next';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="Set new password" description="Please enter your new password below to complete the reset process">
        <Head title="Reset password" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="flex items-center gap-2">
                        <Mail class="h-4 w-4" />
                        Email
                    </Label>
                    <Input 
                        id="email" 
                        type="email" 
                        name="email" 
                        autocomplete="email" 
                        v-model="form.email" 
                        class="border-2 bg-muted/50" 
                        readonly 
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="flex items-center gap-2">
                        <Lock class="h-4 w-4" />
                        New Password
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        v-model="form.password"
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        autofocus
                        placeholder="Enter your new password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="flex items-center gap-2">
                        <Lock class="h-4 w-4" />
                        Confirm Password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        placeholder="Confirm your new password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="group mt-2 w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-else class="flex items-center gap-2">
                        Reset password
                        <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                    </span>
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>
