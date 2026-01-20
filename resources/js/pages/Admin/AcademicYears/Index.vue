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
    academicYears: any;
    filters: {
        search: string;
    };
}>();

const searchQuery = ref(props.filters.search);

const showSheet = ref(false);
const showDeleteDialog = ref(false);
const selectedYear = ref<any>(null);
const isEditing = ref(false);

const form = useForm({
    semester: '',
    start_year: new Date().getFullYear(),
    end_year: new Date().getFullYear() + 1,
});

const deleteForm = useForm({});

const openCreateSheet = () => {
    form.reset();
    form.start_year = new Date().getFullYear();
    form.end_year = new Date().getFullYear() + 1;
    isEditing.value = false;
    selectedYear.value = null;
    showSheet.value = true;
};

const openEditSheet = (year: any) => {
    form.semester = year.semester;
    form.start_year = year.start_year;
    form.end_year = year.end_year;
    selectedYear.value = year;
    isEditing.value = true;
    showSheet.value = true;
};

const openDeleteDialog = (year: any) => {
    selectedYear.value = year;
    showDeleteDialog.value = true;
};

const submit = () => {
    const url = isEditing.value
        ? route('admin.academic-years.update', selectedYear.value.id)
        : route('admin.academic-years.store');
    
    const method = isEditing.value ? 'put' : 'post';
    
    form[method](url, {
        onSuccess: () => {
            toast.success(isEditing.value ? 'Academic year updated successfully.' : 'Academic year created successfully.');
            showSheet.value = false;
            form.reset();
        },
    });
};

const deleteYear = () => {
    if (!selectedYear.value) return;
    
    deleteForm.delete(route('admin.academic-years.destroy', selectedYear.value.id), {
        onSuccess: () => {
            toast.success('Academic year deleted successfully.');
            showDeleteDialog.value = false;
            selectedYear.value = null;
        },
        onError: (errors) => {
            toast.error(errors.error || 'Failed to delete academic year.');
        },
    });
};

const applyFilters = () => {
    router.get(route('admin.academic-years.index'), {
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Academic Years Management" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Academic Years</h1>
                    <p class="text-muted-foreground mt-1.5">
                        Manage academic years and semesters
                    </p>
                </div>
                <Button @click="openCreateSheet" size="lg" class="transition-all duration-200 hover:scale-105">
                    <Plus class="mr-2 h-5 w-5" /> Add Academic Year
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Search</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search by semester or year..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                        <Button @click="applyFilters">Search</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Academic Years Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Academic Years ({{ academicYears.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Semester</TableHead>
                                    <TableHead>Start Year</TableHead>
                                    <TableHead>End Year</TableHead>
                                    <TableHead>Created At</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="year in academicYears.data" :key="year.id">
                                    <TableCell class="font-medium">{{ year.semester }}</TableCell>
                                    <TableCell>{{ year.start_year }}</TableCell>
                                    <TableCell>{{ year.end_year }}</TableCell>
                                    <TableCell>{{ new Date(year.created_at).toLocaleDateString() }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openEditSheet(year)"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openDeleteDialog(year)"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="academicYears.data.length === 0">
                                    <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                                        No academic years found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="academicYears.links && academicYears.links.length > 3" class="mt-4 flex justify-center gap-2">
                        <Button
                            v-for="(link, index) in academicYears.links"
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
                        <SheetTitle>{{ isEditing ? 'Edit' : 'Create' }} Academic Year</SheetTitle>
                        <SheetDescription>
                            {{ isEditing ? 'Update' : 'Add' }} academic year information
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2 mb-4">
                            <Label>Semester</Label>
                            <Input
                                v-model="form.semester"
                                required
                                placeholder="e.g., 1st Semester, 2nd Semester, Midyear Term"
                            />
                            <p v-if="form.errors.semester" class="text-sm text-destructive">{{ form.errors.semester }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="space-y-2">
                                <Label>Start Year</Label>
                                <Input
                                    v-model.number="form.start_year"
                                    type="number"
                                    required
                                    min="2000"
                                    max="2100"
                                />
                                <p v-if="form.errors.start_year" class="text-sm text-destructive">{{ form.errors.start_year }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>End Year</Label>
                                <Input
                                    v-model.number="form.end_year"
                                    type="number"
                                    required
                                    min="2000"
                                    max="2100"
                                />
                                <p v-if="form.errors.end_year" class="text-sm text-destructive">{{ form.errors.end_year }}</p>
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
                        <DialogTitle>Delete Academic Year</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{{ selectedYear?.semester }} {{ selectedYear?.start_year }}-{{ selectedYear?.end_year }}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                        <Button variant="destructive" @click="deleteYear" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
