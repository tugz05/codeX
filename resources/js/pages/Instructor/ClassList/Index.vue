<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import VClassCard from '@/components/VClassCard.vue';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search, Users, BookOpen, TrendingUp } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import AddClass from './Partials/AddClass.vue';
import EditClass from './Partials/EditClass.vue';

const showAddClass = ref(false);
const searchQuery = ref('');

const openSheet = () => {
    showAddClass.value = true;
};

const showEditClass = ref(false);
const selectedClass = ref(null);

const openEditClass = (classItem: any) => {
    selectedClass.value = classItem;
    showEditClass.value = true;
};

const showDeleteConfirm = ref(false);
const selectedClassId = ref<string | null>(null);

const confirmDelete = (id: string) => {
    selectedClassId.value = id;
    showDeleteConfirm.value = true;
};

const deleteClass = () => {
    if (!selectedClassId.value) return;

    router.delete(`/instructor/classlist/${selectedClassId.value}`, {
        onSuccess: () => {
            toast.success('Class deleted successfully.');
            showDeleteConfirm.value = false;
            selectedClassId.value = null;
        },
        onError: () => {
            toast.error('Failed to delete class.');
        },
    });
};

const archiveClass = (id: string) => {
    router.post(`/instructor/classlist/${id}/archive`, {}, {
        onSuccess: () => {
            toast.success('Class archived successfully.');
        },
        onError: () => {
            toast.error('Failed to archive class.');
        },
    });
};

const restoreClass = (id: string) => {
    router.post(`/instructor/classlist/${id}/restore`, {}, {
        onSuccess: () => {
            toast.success('Class restored successfully.');
        },
        onError: () => {
            toast.error('Failed to restore class.');
        },
    });
};

const props = defineProps<{
    classlist: Array<any>;
    archivedClasses?: Array<any>;
    total_classes?: number;
    total_students?: number;
}>();

const filteredClasses = computed(() => {
    if (!searchQuery.value.trim()) return props.classlist;
    const query = searchQuery.value.toLowerCase();
    return props.classlist.filter((cls) => {
        const sectionValue = typeof cls.section === 'string' ? cls.section : cls.section?.name;
        return (
            cls.name.toLowerCase().includes(query) ||
            cls.room?.toLowerCase().includes(query) ||
            sectionValue?.toLowerCase().includes(query) ||
            cls.academic_year?.toLowerCase().includes(query)
        );
    });
});
</script>

<template>
    <Head title="My Classes" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto rounded-xl p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
            <!-- Header Section -->
            <div class="space-y-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">My Classes</h1>
                        <p class="text-muted-foreground mt-1.5">
                            Manage your classes and track student progress
                        </p>
                    </div>
                    <Button @click="openSheet" size="lg" class="transition-all duration-200 hover:scale-105">
                        <Plus class="mr-2 h-5 w-5" /> Create New Class
                    </Button>
                </div>

                <!-- Statistics Cards -->
                <div v-if="props.classlist.length > 0" class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-3">
                    <Card class="border-2 transition-all duration-200 hover:shadow-md">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
                            <BookOpen class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.total_classes || props.classlist.length }}</div>
                            <p class="text-xs text-muted-foreground mt-1">Active classes</p>
                        </CardContent>
                    </Card>
                    <Card class="border-2 transition-all duration-200 hover:shadow-md">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ props.total_students || 0 }}</div>
                            <p class="text-xs text-muted-foreground mt-1">Across all classes</p>
                        </CardContent>
                    </Card>
                    <Card class="border-2 transition-all duration-200 hover:shadow-md">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Average per Class</CardTitle>
                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ props.total_classes > 0 ? Math.round((props.total_students || 0) / props.total_classes) : 0 }}
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">Students per class</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Search Bar -->
            <div v-if="props.classlist.length > 0" class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Search classes by name, room, section, or academic year..."
                    class="pl-10 border-2 transition-all duration-300 focus:scale-[1.01]"
                />
            </div>

            <!-- Classes Grid -->
            <div v-if="filteredClasses.length > 0">
                <TransitionGroup
                    name="card"
                    tag="div"
                    class="grid auto-rows-min grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                >
                    <VClassCard
                        v-for="classItem in filteredClasses"
                        :key="classItem.id"
                        :id="classItem.id"
                        :title="classItem.name"
                        :room="classItem.room"
                        :section="(typeof classItem.section === 'string' ? classItem.section : classItem.section?.name) || 'No section'"
                        :students-count="classItem.students_count || 0"
                        :academic-year="classItem.academic_year"
                        :create-url="route('instructor.activities.index', classItem.id)"
                        @edit="() => openEditClass(classItem)"
                        @delete="confirmDelete"
                    />
                </TransitionGroup>
            </div>

            <!-- Empty State -->
            <div v-else-if="props.classlist.length === 0" class="flex min-h-[60vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
                <div class="mx-auto max-w-md text-center">
                    <BookOpen class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-xl font-semibold mb-2">No classes yet</h3>
                    <p class="text-muted-foreground mb-6">
                        Get started by creating your first class. You can add students, create assignments, and track progress.
                    </p>
                    <Button @click="openSheet" size="lg" class="transition-all duration-200 hover:scale-105">
                        <Plus class="mr-2 h-5 w-5" /> Create Your First Class
                    </Button>
                </div>
            </div>

            <!-- No Search Results -->
            <div v-else class="flex min-h-[40vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
                <div class="mx-auto max-w-md text-center">
                    <Search class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-xl font-semibold mb-2">No classes found</h3>
                    <p class="text-muted-foreground">
                        Try adjusting your search query to find what you're looking for.
                    </p>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteConfirm">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Class</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this class? This action cannot be undone. This will permanently delete the class and all associated data from the system.
                </p>
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="outline" @click="showDeleteConfirm = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteClass">Delete Class</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Class Modal -->
        <AddClass :open="showAddClass" @close="showAddClass = false" />
        <EditClass v-if="selectedClass" :open="showEditClass" :classData="selectedClass" @close="showEditClass = false" />
    </AppLayout>
</template>

<style scoped>
.card-enter-from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
}
.card-enter-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-enter-to {
    opacity: 1;
    transform: scale(1) translateY(0);
}

.card-leave-from {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.card-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
}
</style>
