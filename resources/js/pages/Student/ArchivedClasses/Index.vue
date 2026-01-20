<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import VClassCardStudent from '@/components/VClassCardStudent.vue';
import { toast } from 'vue-sonner';
import { Search, BookOpen, Archive, RotateCcw } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutStudent.vue';

const props = defineProps<{
  archivedClasses: Array<{
    id: string;
    name: string;
    academic_year: string;
    room: string;
    section: { name: string } | null;
    joined_at: string;
    archived_at: string;
  }>;
}>();

const searchQuery = ref('');
const form = useForm({});

const filteredClasses = computed(() => {
  if (!searchQuery.value.trim()) return props.archivedClasses;
  const query = searchQuery.value.toLowerCase();
  return props.archivedClasses.filter((cls) =>
    cls.name?.toLowerCase().includes(query) ||
    cls.room?.toLowerCase().includes(query) ||
    cls.section?.name?.toLowerCase().includes(query) ||
    cls.academic_year?.toLowerCase().includes(query)
  );
});

const restoreClass = (id: string) => {
  form.post(route('student.archived-classes.restore', id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Class restored successfully.');
    },
    onError: () => {
      toast.error('Failed to restore class.');
    },
  });
};

const formatDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
  <Head title="Archived Classes" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Page Header -->
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

      <!-- Archived classes list -->
      <div v-if="filteredClasses.length > 0" class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <Card
          v-for="cls in filteredClasses"
          :key="cls.id"
          class="group relative w-full overflow-hidden rounded-xl border-2 border-border bg-card shadow-sm transition-all duration-300 hover:shadow-lg hover:scale-[1.02]"
        >
          <CardContent class="p-0">
            <!-- Header -->
            <div class="relative px-5 py-4 bg-gradient-to-br from-muted/50 via-muted/30 to-transparent border-b border-border">
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                  <h2 class="truncate text-lg font-bold text-foreground mb-1">
                    {{ cls.name }}
                  </h2>
                  <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                    <div v-if="cls.section && cls.section.name" class="flex items-center gap-1">
                      <BookOpen class="h-3.5 w-3.5" />
                      <span class="truncate">{{ cls.section.name }}</span>
                    </div>
                    <span v-if="cls.section && cls.section.name">•</span>
                    <div class="flex items-center gap-1">
                      <span>Room {{ cls.room }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Content -->
            <div class="px-5 py-4 space-y-3">
              <div v-if="cls.academic_year" class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">Academic Year:</span>
                <span class="text-xs font-medium">{{ cls.academic_year }}</span>
              </div>
              <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span>Archived: {{ formatDate(cls.archived_at) }}</span>
              </div>
            </div>

            <!-- Footer with restore button -->
            <div class="flex items-center justify-end border-t border-border bg-muted/30 px-4 py-2.5">
              <Button
                variant="outline"
                size="sm"
                @click="restoreClass(cls.id)"
                :disabled="form.processing"
                class="w-full"
              >
                <RotateCcw class="mr-2 h-4 w-4" />
                Restore Class
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <div v-else class="flex min-h-[60vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
        <div class="mx-auto max-w-md text-center">
          <Archive class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">No archived classes</h3>
          <p class="text-muted-foreground mb-6">
            You haven't archived any classes yet. Archived classes will appear here.
          </p>
          <Button as-child variant="outline" size="lg">
            <router-link :to="route('student.classlist')">
              <BookOpen class="mr-2 h-5 w-5" /> View My Classes
            </router-link>
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
