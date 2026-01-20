<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { MapPin, GraduationCap, Calendar, User, BookOpen, Clock, Users } from 'lucide-vue-next';

interface Props {
  open: boolean;
  classData: {
    id: string;
    name: string;
    section: string | null;
    room: string;
    academic_year: string;
    instructor?: {
      name: string;
      email: string;
    };
    students_count?: number;
    joined_at?: string;
    created_at?: string;
  } | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

function formatDate(dateString?: string | null): string {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return 'N/A';
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <BookOpen class="h-5 w-5" />
          Class Information
        </DialogTitle>
        <DialogDescription>
          View details about this class
        </DialogDescription>
      </DialogHeader>

      <div v-if="classData" class="space-y-4">
        <!-- Class Name Card -->
        <Card>
          <CardHeader>
            <CardTitle class="text-xl">{{ classData.name }}</CardTitle>
            <CardDescription>Class Name</CardDescription>
          </CardHeader>
        </Card>

        <!-- Class Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Section -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <GraduationCap class="h-4 w-4" />
                Section
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-lg font-semibold">{{ classData.section || 'No section' }}</p>
            </CardContent>
          </Card>

          <!-- Room -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <MapPin class="h-4 w-4" />
                Room
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-lg font-semibold">Room {{ classData.room }}</p>
            </CardContent>
          </Card>

          <!-- Academic Year -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                Academic Year
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="outline" class="text-base font-semibold px-3 py-1">
                AY {{ classData.academic_year }}
              </Badge>
            </CardContent>
          </Card>

          <!-- Students Count -->
          <Card v-if="classData.students_count !== undefined">
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Users class="h-4 w-4" />
                Students
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-lg font-semibold">{{ classData.students_count }} enrolled</p>
            </CardContent>
          </Card>
        </div>

        <!-- Instructor Info -->
        <Card v-if="classData.instructor">
          <CardHeader>
            <CardTitle class="text-sm font-medium flex items-center gap-2">
              <User class="h-4 w-4" />
              Instructor
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-1">
              <p class="text-lg font-semibold">{{ classData.instructor.name }}</p>
              <p class="text-sm text-muted-foreground">{{ classData.instructor.email }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Joined Date -->
          <Card v-if="classData.joined_at">
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Clock class="h-4 w-4" />
                Joined Date
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm font-semibold">{{ formatDate(classData.joined_at) }}</p>
            </CardContent>
          </Card>

          <!-- Created Date -->
          <Card v-if="classData.created_at">
            <CardHeader class="pb-3">
              <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                Class Created
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm font-semibold">{{ formatDate(classData.created_at) }}</p>
            </CardContent>
          </Card>
        </div>
      </div>

      <div v-else class="text-center py-8">
        <p class="text-muted-foreground">Loading class information...</p>
      </div>
    </DialogContent>
  </Dialog>
</template>
