<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import VClassCard from '@/components/VClassCard.vue';
import { toast } from 'vue-sonner';
import { Search, BookOpen, Archive, RotateCcw, Users, TrendingUp } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';

const props = defineProps<{
  archivedClasses: Array<{
    id: string;
    name: string;
    room: string;
    academic_year: string;
    section: { name: string } | null;
    students_count: number;
    created_at: string;
    archived_at: string;
  }>;
  total_classes?: number;
  total_students?: number;
}>();

const searchQuery = ref('');
const form = useForm({});

const filteredClasses = computed(() => {
  if (!searchQuery.value.trim()) return props.archivedClasses;
  const query = searchQuery.value.toLowerCase();
  return props.archivedClasses.filter((cls) =>
    cls.name.toLowerCase().includes(query) ||
    cls.room?.toLowerCase().includes(query) ||
    cls.section?.name?.toLowerCase().includes(query) ||
    cls.academic_year?.toLowerCase().includes(query)
  );
});

const restoreClass = (id: string) => {
  router.post(route('instructor.archived-classes.restore', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Class restored successfully.');
    },
    onError: () => {
      toast.error('Failed to restore class.');
    },
  });
};
</script>

<template>
  <Head title="Archived Classes" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto rounded-xl p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Page Header -->
      <div class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
              <Archive class="h-8 w-8" />
              Archived Classes
            </h1>
            <p class="text-muted-foreground mt-1.5">
              View and restore your archived classes
            </p>
          </div>
          <Button as-child variant="outline" size="lg">
            <Link :href="route('instructor.classlist')">
              <BookOpen class="mr-2 h-5 w-5" /> View Active Classes
            </Link>
          </Button>
        </div>

        <!-- Statistics Cards -->
        <div v-if="props.archivedClasses.length > 0" class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-3">
          <Card class="border-2 transition-all duration-200 hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Archived Classes</CardTitle>
              <Archive class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ props.total_classes || props.archivedClasses.length }}</div>
              <p class="text-xs text-muted-foreground mt-1">Total archived</p>
            </CardContent>
          </Card>
          <Card class="border-2 transition-all duration-200 hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Total Students</CardTitle>
              <Users class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ props.total_students || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">Across archived classes</p>
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
      <div v-if="props.archivedClasses.length > 0" class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          placeholder="Search archived classes by name, room, section, or academic year..."
          class="pl-10 border-2 transition-all duration-300 focus:scale-[1.01]"
        />
      </div>

      <!-- Archived Classes Grid -->
      <div v-if="filteredClasses.length > 0">
        <TransitionGroup
          name="card"
          tag="div"
          class="grid auto-rows-min grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
        >
          <Card
            v-for="classItem in filteredClasses"
            :key="classItem.id"
            class="group relative w-full overflow-hidden rounded-xl border-2 border-border bg-card shadow-sm transition-all duration-300 hover:shadow-lg hover:scale-[1.02]"
          >
            <CardContent class="p-0">
              <!-- Header -->
              <div class="relative px-5 py-4 bg-gradient-to-br from-muted/50 via-muted/30 to-transparent border-b border-border">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <h2 class="truncate text-lg font-bold text-foreground mb-1">
                      {{ classItem.name }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                      <div class="flex items-center gap-1">
                        <BookOpen class="h-3.5 w-3.5" />
                        <span class="truncate">{{ classItem.section?.name || 'No section' }}</span>
                      </div>
                      <span>•</span>
                      <div class="flex items-center gap-1">
                        <span>Room {{ classItem.room }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Content -->
              <div class="px-5 py-4 space-y-3">
                <div class="flex items-center gap-4">
                  <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                      <Users class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                      <p class="text-xs text-muted-foreground">Students</p>
                      <p class="text-sm font-semibold">{{ classItem.students_count || 0 }}</p>
                    </div>
                  </div>
                  <div v-if="classItem.academic_year" class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">AY {{ classItem.academic_year }}</span>
                  </div>
                </div>
              </div>

              <!-- Footer with restore button -->
              <div class="flex items-center justify-end border-t border-border bg-muted/30 px-4 py-2.5">
                <Button
                  variant="outline"
                  size="sm"
                  @click="restoreClass(classItem.id)"
                  :disabled="form.processing"
                  class="w-full"
                >
                  <RotateCcw class="mr-2 h-4 w-4" />
                  Restore Class
                </Button>
              </div>
            </CardContent>
          </Card>
        </TransitionGroup>
      </div>

      <!-- Empty State -->
      <div v-else-if="props.archivedClasses.length === 0" class="flex min-h-[60vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
        <div class="mx-auto max-w-md text-center">
          <Archive class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">No archived classes</h3>
          <p class="text-muted-foreground mb-6">
            You haven't archived any classes yet. Archived classes will appear here.
          </p>
          <Button as-child variant="outline" size="lg">
            <Link :href="route('instructor.classlist')">
              <BookOpen class="mr-2 h-5 w-5" /> View My Classes
            </Link>
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
  </AppLayout>
</template>

<style scoped>
.card-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}
.card-enter-to {
  opacity: 1;
  transform: scale(1) translateY(0);
}
.card-enter-active {
  transition: all 0.3s ease-out;
}
.card-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}
.card-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}
.card-leave-active {
  transition: all 0.3s ease-in;
}
</style>
