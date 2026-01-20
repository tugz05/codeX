<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutAdmin.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Edit, Trash2, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    users: any;
    filters: {
        account_type: string;
        search: string;
    };
}>();

const searchQuery = ref(props.filters.search);
const accountTypeFilter = ref(props.filters.account_type);

const showCreateSheet = ref(false);
const showEditSheet = ref(false);
const showViewSheet = ref(false);
const showDeleteDialog = ref(false);
const selectedUser = ref<any>(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    account_type: 'student',
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    account_type: 'student',
});

const deleteForm = useForm({});

const openCreateSheet = () => {
    createForm.reset();
    createForm.account_type = 'student';
    showCreateSheet.value = true;
};

const openEditSheet = (user: any) => {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.account_type = user.account_type;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEditSheet.value = true;
};

const openViewSheet = async (user: any) => {
    selectedUser.value = user;
    showViewSheet.value = true;
    
    // Fetch full user details with relationships
    try {
        const response = await fetch(route('admin.users.show', user.id), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            const data = await response.json();
            selectedUser.value = data.user;
        }
    } catch (error) {
        // Use the user data from table if fetch fails
        console.error('Failed to fetch user details:', error);
    }
};

const openDeleteDialog = (user: any) => {
    selectedUser.value = user;
    showDeleteDialog.value = true;
};

const createUser = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            toast.success('User created successfully.');
            showCreateSheet.value = false;
            createForm.reset();
        },
    });
};

const updateUser = () => {
    if (!selectedUser.value) return;
    
    editForm.put(route('admin.users.update', selectedUser.value.id), {
        onSuccess: () => {
            toast.success('User updated successfully.');
            showEditSheet.value = false;
            selectedUser.value = null;
        },
    });
};

const deleteUser = () => {
    if (!selectedUser.value) return;
    
    deleteForm.delete(route('admin.users.destroy', selectedUser.value.id), {
        onSuccess: () => {
            toast.success('User deleted successfully.');
            showDeleteDialog.value = false;
            selectedUser.value = null;
        },
        onError: () => {
            toast.error('Failed to delete user.');
        },
    });
};

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        account_type: accountTypeFilter.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getAccountTypeBadgeVariant = (type: string) => {
    switch (type) {
        case 'admin':
            return 'destructive';
        case 'instructor':
            return 'default';
        case 'student':
            return 'secondary';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head title="User Management" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                    <p class="text-muted-foreground mt-1.5">
                        Manage all users (students, instructors, and admins)
                    </p>
                </div>
                <Button @click="openCreateSheet" size="lg" class="transition-all duration-200 hover:scale-105">
                    <Plus class="mr-2 h-5 w-5" /> Add User
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
                                    placeholder="Search by name or email..."
                                    class="pl-10"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>
                        <Select v-model="accountTypeFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-full sm:w-[200px]">
                                <SelectValue placeholder="Account Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="student">Students</SelectItem>
                                <SelectItem value="instructor">Instructors</SelectItem>
                                <SelectItem value="admin">Admins</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="applyFilters">Apply Filters</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Users ({{ users.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Account Type</TableHead>
                                    <TableHead>Created At</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id">
                                    <TableCell class="font-medium">{{ user.name }}</TableCell>
                                    <TableCell>{{ user.email }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="getAccountTypeBadgeVariant(user.account_type)">
                                            {{ user.account_type }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ new Date(user.created_at).toLocaleDateString() }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openViewSheet(user)"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openEditSheet(user)"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="openDeleteDialog(user)"
                                                :disabled="user.id === $page.props.auth.user?.id"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="users.data.length === 0">
                                    <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links && users.links.length > 3" class="mt-4 flex justify-center gap-2">
                        <Button
                            v-for="(link, index) in users.links"
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

            <!-- Create User Sheet -->
            <Sheet v-model:open="showCreateSheet">
                <SheetContent class="overflow-y-auto p-6">
                    <SheetHeader class="mb-6">
                        <SheetTitle>Create User</SheetTitle>
                        <SheetDescription>
                            Add a new user to the system
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="createUser" class="space-y-6">
                        <div class="space-y-2 mb-4">
                            <Label for="create-name">Name</Label>
                            <Input
                                id="create-name"
                                v-model="createForm.name"
                                required
                                placeholder="John Doe"
                            />
                            <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="create-email">Email</Label>
                            <Input
                                id="create-email"
                                type="email"
                                v-model="createForm.email"
                                required
                                placeholder="john@example.com"
                            />
                            <p v-if="createForm.errors.email" class="text-sm text-destructive">{{ createForm.errors.email }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="create-account_type">Account Type</Label>
                            <Select v-model="createForm.account_type">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="student">Student</SelectItem>
                                    <SelectItem value="instructor">Instructor</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="createForm.errors.account_type" class="text-sm text-destructive">{{ createForm.errors.account_type }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="create-password">Password</Label>
                            <Input
                                id="create-password"
                                type="password"
                                v-model="createForm.password"
                                required
                                placeholder="Minimum 8 characters"
                            />
                            <p v-if="createForm.errors.password" class="text-sm text-destructive">{{ createForm.errors.password }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="create-password_confirmation">Confirm Password</Label>
                            <Input
                                id="create-password_confirmation"
                                type="password"
                                v-model="createForm.password_confirmation"
                                required
                                placeholder="Re-enter password"
                            />
                        </div>

                        <SheetFooter class="mt-6">
                            <Button type="button" variant="outline" @click="showCreateSheet = false">Cancel</Button>
                            <Button type="submit" :disabled="createForm.processing">
                                {{ createForm.processing ? 'Creating...' : 'Create User' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <!-- Edit User Sheet -->
            <Sheet v-model:open="showEditSheet">
                <SheetContent class="overflow-y-auto p-6">
                    <SheetHeader class="mb-6">
                        <SheetTitle>Edit User</SheetTitle>
                        <SheetDescription>
                            Update user information
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="updateUser" class="space-y-6" v-if="selectedUser">
                        <div class="space-y-2 mb-4">
                            <Label for="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                v-model="editForm.name"
                                required
                                placeholder="John Doe"
                            />
                            <p v-if="editForm.errors.name" class="text-sm text-destructive">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="edit-email">Email</Label>
                            <Input
                                id="edit-email"
                                type="email"
                                v-model="editForm.email"
                                required
                                placeholder="john@example.com"
                            />
                            <p v-if="editForm.errors.email" class="text-sm text-destructive">{{ editForm.errors.email }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="edit-account_type">Account Type</Label>
                            <Select v-model="editForm.account_type">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="student">Student</SelectItem>
                                    <SelectItem value="instructor">Instructor</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.errors.account_type" class="text-sm text-destructive">{{ editForm.errors.account_type }}</p>
                        </div>

                        <div class="space-y-2 mb-4">
                            <Label for="edit-password">New Password (leave blank to keep current)</Label>
                            <Input
                                id="edit-password"
                                type="password"
                                v-model="editForm.password"
                                placeholder="Minimum 8 characters"
                            />
                            <p v-if="editForm.errors.password" class="text-sm text-destructive">{{ editForm.errors.password }}</p>
                        </div>

                        <div v-if="editForm.password" class="space-y-2 mb-4">
                            <Label for="edit-password_confirmation">Confirm New Password</Label>
                            <Input
                                id="edit-password_confirmation"
                                type="password"
                                v-model="editForm.password_confirmation"
                                placeholder="Re-enter password"
                            />
                        </div>

                        <SheetFooter class="mt-6">
                            <Button type="button" variant="outline" @click="showEditSheet = false">Cancel</Button>
                            <Button type="submit" :disabled="editForm.processing">
                                {{ editForm.processing ? 'Updating...' : 'Update User' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <!-- View User Sheet -->
            <Sheet v-model:open="showViewSheet">
                <SheetContent class="overflow-y-auto p-6">
                    <SheetHeader class="mb-6">
                        <SheetTitle>{{ selectedUser?.name }}</SheetTitle>
                        <SheetDescription>
                            User details and information
                        </SheetDescription>
                    </SheetHeader>
                    <div class="space-y-6" v-if="selectedUser">
                        <div class="space-y-4 mb-6">
                            <div class="mb-4">
                                <p class="text-sm text-muted-foreground mb-1">Name</p>
                                <p class="text-base font-medium">{{ selectedUser.name }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-muted-foreground mb-1">Email</p>
                                <p class="text-base font-medium">{{ selectedUser.email }}</p>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-muted-foreground mb-1">Account Type</p>
                                <Badge :variant="getAccountTypeBadgeVariant(selectedUser.account_type)" class="mt-1">
                                    {{ selectedUser.account_type }}
                                </Badge>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-muted-foreground mb-1">Created At</p>
                                <p class="text-base font-medium">{{ new Date(selectedUser.created_at).toLocaleString() }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 pt-4 border-t mb-6">
                            <div>
                                <p class="text-sm text-muted-foreground">Classes Created</p>
                                <p class="text-2xl font-bold">{{ selectedUser.classlists?.length || 0 }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Classes Enrolled</p>
                                <p class="text-2xl font-bold">{{ selectedUser.enrolled_classes?.length || 0 }}</p>
                            </div>
                        </div>

                        <SheetFooter class="mt-6">
                            <Button variant="outline" @click="showViewSheet = false">Close</Button>
                            <Button @click="() => { showViewSheet = false; openEditSheet(selectedUser); }">
                                <Edit class="mr-2 h-4 w-4" /> Edit User
                            </Button>
                        </SheetFooter>
                    </div>
                </SheetContent>
            </Sheet>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{{ selectedUser?.name }}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                        <Button variant="destructive" @click="deleteUser" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
