<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutAdmin.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Label } from '@/components/ui/label';
import { Plus, Search, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    sections: any;
    instructors: Array<{ id: number; name: string; email: string }>;
    filters: {
        instructor_id: string;
        search: string;
    };
}>();

const searchQuery = ref(props.filters.search);
const instructorFilter = ref(props.filters.instructor_id);

const showSheet = ref(false);
const showDeleteDialog = ref(false);
const selectedSection = ref<any>(null);
const isEditing = ref(false);

const form = useForm({
    user_id: '',
    name: '',
    schedule_from: '',
    schedule_to: '',
    day: '',
});

const deleteForm = useForm({});

const openCreateSheet = () => {
    form.reset();
    isEditing.value = false;
    selectedSection.value = null;
    showSheet.value = true;
};

const openEditSheet = (section: any) => {
    form.user_id = section.user_id.toString();
    form.name = section.name;
    form.schedule_from = section.schedule_from;
    form.schedule_to = section.schedule_to;
    form.day = section.day;
    selectedSection.value = section;
    isEditing.value = true;
    showSheet.value = true;
};

const openDeleteDialog = (section: any) => {
    selectedSection.value = section;
    showDeleteDialog.value = true;
};

const submit = () => {
    const url = isEditing.value
        ? route('admin.sections.update', selectedSection.value.id)
        : route('admin.sections.store');
    
    const method = isEditing.value ? 'put' : 'post';
    
    form[method](url, {
        onSuccess: () => {
            toast.success(isEditing.value ? 'Section updated successfully.' : 'Section created successfully.');
            showSheet.value = false;
            form.reset();
        },
    });
};

const deleteSection = () => {
    if (!selectedSection.value) return;
    
    deleteForm.delete(route('admin.sections.destroy', selectedSection.value.id), {
        onSuccess: () => {
            toast.success('Section deleted successfully.');
            showDeleteDialog.value = false;
            selectedSection.value = null;
        },
        onError: (errors) => {
            toast.error(errors.error || 'Failed to delete section.');
        },
    });
};

const applyFilters = () => {
    router.get(route('admin.sections.index'), {
        instructor_id: instructorFilter.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
</script>

<template>
    <Head title="Sections Management" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Sections</h1>
                    <p class="text-muted-foreground mt-1.5">
                        Manage class sections and schedules
                    </p>
                </div>
                <Button @click="openCreateSheet" size="lg" class="transition-all duration-200 hover:scale-105">
                    <Plus class="mr-2 h-5 w-5" /> Add Section
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by name, day, or instructor..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                        <Select v-model="instructorFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-full sm:w-[200px]">
                                <SelectValue placeholder="Instructor" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Instructors</SelectItem>
                                <SelectItem v-for="instructor in instructors" :key="instructor.id" :value="instructor.id.toString()">
                                    {{ instructor.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="applyFilters">Apply Filters</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Sections Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Sections ({{ sections.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Instructor</TableHead>
                                    <TableHead>Day</TableHead>
                                    <TableHead>Schedule</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="section in sections.data" :key="section.id">
                                    <TableCell class="font-medium">{{ section.name }}</TableCell>
                                    <TableCell>{{ section.user?.name }}</TableCell>
                                    <TableCell>{{ section.day }}</TableCell>
                                    <TableCell>{{ section.schedule_from }} - {{ section.schedule_to }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openEditSheet(section)"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openDeleteDialog(section)"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="sections.data.length === 0">
                                    <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                                        No sections found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="sections.links && sections.links.length > 3" class="mt-4 flex justify-center gap-2">
                        <Button
                            v-for="(link, index) in sections.links"
                            :key="index"
                            variant="outline"
                            size="sm"
                            :disabled="!link.url"
                            @click="link.url && router.visit(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Create/Edit Sheet -->
            <Sheet v-model:open="showSheet">
                <SheetContent class="overflow-y-auto p-6">
                    <SheetHeader class="mb-6">
                        <SheetTitle>{{ isEditing ? 'Edit' : 'Create' }} Section</SheetTitle>
                        <SheetDescription>
                            {{ isEditing ? 'Update' : 'Add' }} section information
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2 mb-4">
                            <Label>Instructor</Label>
                            <Select v-model="form.user_id" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select instructor" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="instructor in instructors" :key="instructor.id" :value="instructor.id.toString()">
                                        {{ instructor.name }} ({{ instructor.email }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.user_id" class="text-sm text-destructive">{{ form.errors.user_id }}</p>
                        </div>
                        <div class="space-y-2 mb-4">
                            <Label>Section Name</Label>
                            <Input
                                v-model="form.name"
                                required
                                placeholder="e.g., Section A, CS-101"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2 mb-4">
                            <Label>Day</Label>
                            <Select v-model="form.day" required>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select day" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="day in daysOfWeek" :key="day" :value="day">
                                        {{ day }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.day" class="text-sm text-destructive">{{ form.errors.day }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="space-y-2">
                                <Label>Start Time</Label>
                                <Input
                                    v-model="form.schedule_from"
                                    type="time"
                                    required
                                />
                                <p v-if="form.errors.schedule_from" class="text-sm text-destructive">{{ form.errors.schedule_from }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>End Time</Label>
                                <Input
                                    v-model="form.schedule_to"
                                    type="time"
                                    required
                                />
                                <p v-if="form.errors.schedule_to" class="text-sm text-destructive">{{ form.errors.schedule_to }}</p>
                            </div>
                        </div>
                        <SheetFooter class="mt-6">
                            <Button type="button" variant="outline" @click="showSheet = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? (isEditing ? 'Updating...' : 'Creating...') : (isEditing ? 'Update' : 'Create') }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Section</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{{ selectedSection?.name }}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                        <Button variant="destructive" @click="deleteSection" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
