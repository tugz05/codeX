<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Lock, Shield, ArrowRight } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout title="Security confirmation" description="This is a secure area. Please confirm your password to continue">
        <Head title="Confirm password" />

        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-center rounded-lg border-2 border-primary/20 bg-primary/5 p-4">
                <Shield class="h-8 w-8 text-primary" />
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="password" class="flex items-center gap-2">
                        <Lock class="h-4 w-4" />
                        Password
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="Enter your password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Button type="submit" class="group w-full border-2 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-else class="flex items-center gap-2">
                        Confirm Password
                        <ArrowRight class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                    </span>
                </Button>
            </form>
        </div>
    </AuthLayout>
</template>
