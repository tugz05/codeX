<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
const props = defineProps<{ open: boolean }>();
const emit = defineEmits(['close']);

// Local state for the sheet
const isOpen = ref(props.open);
watch(
    () => props.open,
    (val) => (isOpen.value = val),
);

// Form state
const form = ref({
    name: '',
    section_id: '',
    academic_year: '',
    room: '',
});

// Example section options
const sections = ref([
    { id: 1, name: 'BSCS 1-A' },
    { id: 2, name: 'BSCS 2-B' },
]);

const submitForm = () => {
    router.post('/instructor/classlist/add', form.value, {
        onSuccess: () => {
            toast('Class has been created', {
                description: new Intl.DateTimeFormat('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                }).format(new Date()),
                action: {
                    label: 'Undo',
                    onClick: () => console.log('Undo'),
                },
            });
            form.value = { name: '', section_id: '', academic_year: '', room: '' };
            emit('close');
        },
        onError: () => {
            toast.error('Something went wrong. Please try again.');
        },
    });
};
</script>

<template>
    <Sheet :open="isOpen" @update:open="emit('close')">
        <SheetContent class="w-[400px] sm:w-[540px]">
            <SheetHeader>
                <SheetTitle>Add Class</SheetTitle>
                <SheetDescription> Fill out the details below to create a new class. </SheetDescription>
            </SheetHeader>

            <div class="grid gap-6 px-2 py-4">
                <!-- Class Name -->
                <div class="flex flex-col gap-2">
                    <Label for="name" class="font-medium">Class Name</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Introduction to Programming" />
                </div>

                <!-- Section -->
                <div class="flex flex-col gap-2">
                    <Label for="section" class="font-medium">Section</Label>
                    <Select v-model="form.section_id">
                        <SelectTrigger id="section">
                            <SelectValue placeholder="Select section" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="section in sections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Academic Year -->
                <div class="flex flex-col gap-2">
                    <Label for="academic_year" class="font-medium">Academic Year</Label>
                    <Input id="academic_year" v-model="form.academic_year" placeholder="e.g. 2024-2025" />
                </div>

                <!-- Room -->
                <div class="flex flex-col gap-2">
                    <Label for="room" class="font-medium">Room</Label>
                    <Input id="room" v-model="form.room" placeholder="e.g. Room 101" />
                </div>
            </div>

            <SheetFooter>
                <SheetClose as-child>
                    <Button type="button" @click="submitForm">Save Class</Button>
                </SheetClose>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
