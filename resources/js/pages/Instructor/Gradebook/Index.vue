<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { ArrowLeft, Download, FileSpreadsheet, FileText, BarChart3, ArrowUp, ArrowDown } from 'lucide-vue-next';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  type ChartData,
  type ChartOptions
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed, ref } from 'vue';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

interface Props {
  classlist: {
    id: string;
    name: string;
    academic_year: string;
    room: string;
  };
  graded_items: Array<{
    id: number;
    type: string;
    title: string;
    points: number;
    due_date: string | null;
  }>;
  student_grades: Array<{
    student: {
      id: number;
      name: string;
      email: string;
    };
    grades: Array<{
      item_id: number;
      item_type: string;
      item_title: string;
      score: number | null;
      points: number;
      percentage: number | null;
      submitted_at: string | null;
    }>;
    total_points: number;
    earned_points: number;
    overall_percentage: number;
    attendance_percentage?: number;
  }>;
  distribution: {
    A: number;
    B: number;
    C: number;
    D: number;
    F: number;
  };
}

const props = defineProps<Props>();

const distributionData = computed<ChartData<'doughnut'>>(() => ({
  labels: ['A (90-100%)', 'B (80-89%)', 'C (70-79%)', 'D (60-69%)', 'F (0-59%)'],
  datasets: [
    {
      label: 'Students',
      data: [
        props.distribution.A,
        props.distribution.B,
        props.distribution.C,
        props.distribution.D,
        props.distribution.F,
      ],
      backgroundColor: [
        'rgba(34, 197, 94, 0.8)',
        'rgba(59, 130, 246, 0.8)',
        'rgba(234, 179, 8, 0.8)',
        'rgba(251, 146, 60, 0.8)',
        'rgba(239, 68, 68, 0.8)',
      ],
      borderColor: [
        'rgb(34, 197, 94)',
        'rgb(59, 130, 246)',
        'rgb(234, 179, 8)',
        'rgb(251, 146, 60)',
        'rgb(239, 68, 68)',
      ],
      borderWidth: 2,
    },
  ],
}));

const distributionOptions: ChartOptions<'doughnut'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
    title: {
      display: true,
      text: 'Grade Distribution',
    },
  },
};

const getGradeColor = (percentage: number | null) => {
  if (percentage === null) return 'text-muted-foreground';
  if (percentage >= 90) return 'text-green-600';
  if (percentage >= 80) return 'text-blue-600';
  if (percentage >= 70) return 'text-yellow-600';
  if (percentage >= 60) return 'text-orange-600';
  return 'text-red-600';
};

const getGradeBadge = (percentage: number | null) => {
  if (percentage === null) return { label: 'N/A', variant: 'secondary' as const };
  if (percentage >= 90) return { label: 'A', variant: 'default' as const };
  if (percentage >= 80) return { label: 'B', variant: 'default' as const };
  if (percentage >= 70) return { label: 'C', variant: 'default' as const };
  if (percentage >= 60) return { label: 'D', variant: 'default' as const };
  return { label: 'F', variant: 'destructive' as const };
};

const getItemTypeIcon = (type: string) => {
  switch (type) {
    case 'activity':
      return '💻';
    case 'assignment':
      return '📝';
    case 'quiz':
      return '📋';
    case 'examination':
      return '📝';
    default:
      return '📄';
  }
};

// Sorting state
type StudentSortField = 'name' | 'email' | 'percentage' | 'total_points';
type ItemSortField = 'title' | 'type' | 'points' | 'due_date';
type SortDirection = 'asc' | 'desc';

const studentSortField = ref<StudentSortField>('name');
const studentSortDirection = ref<SortDirection>('asc');
const itemSortField = ref<ItemSortField>('title');
const itemSortDirection = ref<SortDirection>('asc');

const toggleStudentSortDirection = () => {
  studentSortDirection.value = studentSortDirection.value === 'asc' ? 'desc' : 'asc';
};

const toggleItemSortDirection = () => {
  itemSortDirection.value = itemSortDirection.value === 'asc' ? 'desc' : 'asc';
};

const sortedStudents = computed(() => {
  const sorted = [...props.student_grades].sort((a, b) => {
    let comparison = 0;

    switch (studentSortField.value) {
      case 'name':
        comparison = a.student.name.localeCompare(b.student.name);
        break;
      case 'email':
        comparison = a.student.email.localeCompare(b.student.email);
        break;
      case 'percentage':
        comparison = (a.overall_percentage || 0) - (b.overall_percentage || 0);
        break;
      case 'total_points':
        comparison = a.earned_points - b.earned_points;
        break;
    }

    return studentSortDirection.value === 'asc' ? comparison : -comparison;
  });

  return sorted;
});

const sortedGradedItems = computed(() => {
  const sorted = [...props.graded_items].sort((a, b) => {
    let comparison = 0;

    switch (itemSortField.value) {
      case 'title':
        comparison = a.title.localeCompare(b.title);
        break;
      case 'type':
        comparison = a.type.localeCompare(b.type);
        break;
      case 'points':
        comparison = a.points - b.points;
        break;
      case 'due_date':
        const dateA = a.due_date ? new Date(a.due_date).getTime() : 0;
        const dateB = b.due_date ? new Date(b.due_date).getTime() : 0;
        comparison = dateA - dateB;
        break;
    }

    return itemSortDirection.value === 'asc' ? comparison : -comparison;
  });

  return sorted;
});
</script>

<template>
  <Head :title="`Gradebook - ${props.classlist.name}`" />
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-3 sm:gap-4 rounded-xl p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" @click="router.visit(route('instructor.classlist'))">
            <ArrowLeft class="h-4 w-4 mr-1" /> Back
          </Button>
          <div>
            <h1 class="text-xl font-semibold">Gradebook</h1>
            <p class="text-sm text-muted-foreground">
              {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button
            variant="outline"
            @click="router.visit(route('instructor.gradebook.export.excel', props.classlist.id))"
          >
            <FileSpreadsheet class="h-4 w-4 mr-2" />
            Export Excel
          </Button>
          <Button
            variant="outline"
            @click="router.visit(route('instructor.gradebook.export.pdf', props.classlist.id))"
          >
            <FileText class="h-4 w-4 mr-2" />
            Export PDF
          </Button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.student_grades.length }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Grade</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ props.student_grades.length > 0 
                ? (props.student_grades.reduce((sum, sg) => sum + sg.overall_percentage, 0) / props.student_grades.length).toFixed(1)
                : 0 }}%
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Graded Items</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.graded_items.length }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Passing Rate</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ props.student_grades.length > 0
                ? ((props.student_grades.filter(sg => sg.overall_percentage >= 60).length / props.student_grades.length) * 100).toFixed(1)
                : 0 }}%
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Grade Distribution Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Grade Distribution</CardTitle>
          <CardDescription>Distribution of student grades across the class</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-64">
            <Doughnut :data="distributionData" :options="distributionOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Gradebook Table -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Student Grades</CardTitle>
              <CardDescription>Detailed grade breakdown for each student</CardDescription>
            </div>
            <div class="flex items-center gap-4">
              <!-- Student Sorting -->
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Sort Students:</span>
                <Select v-model="studentSortField">
                  <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Sort by" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="name">Name</SelectItem>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="percentage">Percentage</SelectItem>
                    <SelectItem value="total_points">Total Points</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" @click="toggleStudentSortDirection" :title="studentSortDirection === 'asc' ? 'Ascending' : 'Descending'">
                  <ArrowUp v-if="studentSortDirection === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else class="h-4 w-4" />
                </Button>
              </div>
              <!-- Item Sorting -->
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Sort Items:</span>
                <Select v-model="itemSortField">
                  <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Sort by" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="title">Title</SelectItem>
                    <SelectItem value="type">Type</SelectItem>
                    <SelectItem value="points">Points</SelectItem>
                    <SelectItem value="due_date">Due Date</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" @click="toggleItemSortDirection" :title="itemSortDirection === 'asc' ? 'Ascending' : 'Descending'">
                  <ArrowUp v-if="itemSortDirection === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="sticky left-0 bg-background z-10">Student</TableHead>
                  <TableHead
                    v-for="item in sortedGradedItems"
                    :key="`${item.type}-${item.id}`"
                    class="text-center min-w-[120px]"
                  >
                    <div class="flex flex-col items-center gap-1">
                      <span class="text-xs">{{ getItemTypeIcon(item.type) }}</span>
                      <span class="text-xs font-normal">{{ item.title }}</span>
                      <span class="text-xs text-muted-foreground">({{ item.points }} pts)</span>
                    </div>
                  </TableHead>
                  <TableHead class="text-center">Total</TableHead>
                  <TableHead class="text-center">Average</TableHead>
                  <TableHead class="text-center">Attendance</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="studentGrade in sortedStudents" :key="studentGrade.student.id">
                  <TableCell class="sticky left-0 bg-background z-10 font-medium">
                    <div>
                      <div>{{ studentGrade.student.name }}</div>
                      <div class="text-xs text-muted-foreground">{{ studentGrade.student.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell
                    v-for="item in sortedGradedItems"
                    :key="`${item.type}-${item.id}`"
                    class="text-center"
                  >
                    <div v-if="studentGrade.grades.find(g => g.item_id === item.id && g.item_type === item.type)">
                      <div
                        :class="getGradeColor(
                          studentGrade.grades.find(g => g.item_id === item.id && g.item_type === item.type)?.percentage
                        )"
                      >
                        {{
                          studentGrade.grades.find(g => g.item_id === item.id && g.item_type === item.type)?.score ??
                          '-'
                        }}/{{ item.points }}
                      </div>
                      <div class="text-xs text-muted-foreground">
                        {{
                          studentGrade.grades.find(g => g.item_id === item.id && g.item_type === item.type)
                            ?.percentage
                            ? `${studentGrade.grades.find(g => g.item_id === item.id && g.item_type === item.type)?.percentage}%`
                            : '-'
                        }}
                      </div>
                    </div>
                    <div v-else class="text-muted-foreground">-</div>
                  </TableCell>
                  <TableCell class="text-center font-medium">
                    {{ studentGrade.earned_points }}/{{ studentGrade.total_points }}
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge :variant="getGradeBadge(studentGrade.overall_percentage).variant">
                      {{ studentGrade.overall_percentage.toFixed(1) }}%
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge v-if="studentGrade.attendance_percentage !== undefined" :variant="studentGrade.attendance_percentage >= 80 ? 'default' : 'destructive'">
                      {{ studentGrade.attendance_percentage?.toFixed(1) ?? 'N/A' }}%
                    </Badge>
                    <span v-else class="text-muted-foreground">N/A</span>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
