<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AuthLayoutStudent.vue';
import { BookOpen, TrendingUp, Award, BarChart3, Download, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  type ChartData,
  type ChartOptions
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { computed, ref } from 'vue';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

interface Props {
  class_grades: Array<{
    classlist: {
      id: string;
      name: string;
      academic_year: string;
      section: { id: number; name: string } | null;
    };
    grades: Array<{
      id: number;
      type: string;
      title: string;
      score: number;
      points: number;
      percentage: number;
      submitted_at: string | null;
      graded_at: string;
    }>;
    statistics: {
      total_items: number;
      total_points: number;
      earned_points: number;
      average_percentage: number;
      overall_percentage: number;
    };
    grade_history: Array<{
      date: string;
      percentage: number;
    }>;
  }>;
  overall_stats: {
    total_classes: number;
    total_items: number;
    total_points: number;
    earned_points: number;
    average_percentage: number;
    overall_percentage: number;
  };
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
      label: 'Grades',
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

const formatPercentage = (percentage: number | string | null | undefined): string => {
  if (percentage === null || percentage === undefined) return 'N/A';
  const num = typeof percentage === 'string' ? parseFloat(percentage) : percentage;
  if (isNaN(num)) return 'N/A';
  return num.toFixed(1) + '%';
};

const getGradeColor = (percentage: number | string | null | undefined) => {
  if (percentage === null || percentage === undefined) return 'text-muted-foreground';
  const num = typeof percentage === 'string' ? parseFloat(percentage) : percentage;
  if (isNaN(num)) return 'text-muted-foreground';
  if (num >= 90) return 'text-green-600';
  if (num >= 80) return 'text-blue-600';
  if (num >= 70) return 'text-yellow-600';
  if (num >= 60) return 'text-orange-600';
  return 'text-red-600';
};

const getGradeBadge = (percentage: number | string | null | undefined) => {
  if (percentage === null || percentage === undefined) return { label: 'N/A', variant: 'secondary' as const };
  const num = typeof percentage === 'string' ? parseFloat(percentage) : percentage;
  if (isNaN(num)) return { label: 'N/A', variant: 'secondary' as const };
  if (num >= 90) return { label: 'A', variant: 'default' as const };
  if (num >= 80) return { label: 'B', variant: 'default' as const };
  if (num >= 70) return { label: 'C', variant: 'default' as const };
  if (num >= 60) return { label: 'D', variant: 'default' as const };
  return { label: 'F', variant: 'destructive' as const };
};

const getTypeIcon = (type: string) => {
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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Sorting state
type SortField = 'title' | 'percentage' | 'date' | 'type' | 'score';
type SortDirection = 'asc' | 'desc';

const sortField = ref<SortField>('date');
const sortDirection = ref<SortDirection>('desc');

const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
};

const sortGrades = (grades: Array<{
  id: number;
  type: string;
  title: string;
  score: number;
  points: number;
  percentage: number;
  submitted_at: string | null;
  graded_at: string;
}>) => {
  const sorted = [...grades].sort((a, b) => {
    let comparison = 0;

    switch (sortField.value) {
      case 'title':
        comparison = a.title.localeCompare(b.title);
        break;
      case 'percentage':
        comparison = (a.percentage || 0) - (b.percentage || 0);
        break;
      case 'date':
        comparison = new Date(a.graded_at).getTime() - new Date(b.graded_at).getTime();
        break;
      case 'type':
        comparison = a.type.localeCompare(b.type);
        break;
      case 'score':
        comparison = a.score - b.score;
        break;
    }

    return sortDirection.value === 'asc' ? comparison : -comparison;
  });

  return sorted;
};
</script>

<template>
  <Head title="My Grades" />
  <AppLayout>
    <div class="container mx-auto p-3 sm:p-4 md:p-6 max-w-[1600px] w-full">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold">My Grades</h1>
        <p class="text-muted-foreground">View your grades across all enrolled classes</p>
      </div>

      <!-- Overall Statistics -->
      <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-4 mb-4 sm:mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Overall Average</CardTitle>
            <Award class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ formatPercentage(props.overall_stats.overall_percentage) }}</div>
            <Badge
              :variant="getGradeBadge(props.overall_stats.overall_percentage).variant"
              :class="getGradeBadge(props.overall_stats.overall_percentage).variant === 'destructive' ? 'mt-1 text-white' : 'mt-1'"
            >
              {{ getGradeBadge(props.overall_stats.overall_percentage).label }}
            </Badge>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
            <BookOpen class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.overall_stats.total_classes }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Graded Items</CardTitle>
            <BarChart3 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.overall_stats.total_items }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Points</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ props.overall_stats.earned_points }}/{{ props.overall_stats.total_points }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Grade Distribution Chart -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Grade Distribution</CardTitle>
          <CardDescription>Distribution of your grades across all classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-64">
            <Doughnut :data="distributionData" :options="distributionOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Class Grades -->
      <Tabs default-value="all" class="space-y-4">
        <TabsList>
          <TabsTrigger value="all">All Classes</TabsTrigger>
          <TabsTrigger
            v-for="classGrade in props.class_grades"
            :key="classGrade.classlist.id"
            :value="classGrade.classlist.id"
          >
            {{ classGrade.classlist.name }}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="all" class="space-y-4">
          <Card
            v-for="classGrade in props.class_grades"
            :key="classGrade.classlist.id"
          >
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>{{ classGrade.classlist.name }}</CardTitle>
                  <CardDescription>
                    AY {{ classGrade.classlist.academic_year }}
                    <span v-if="classGrade.classlist.section">
                      • {{ classGrade.classlist.section.name }}
                    </span>
                  </CardDescription>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold">
                    {{ formatPercentage(classGrade.statistics.overall_percentage) }}
                  </div>
                  <Badge
                    :variant="getGradeBadge(classGrade.statistics.overall_percentage).variant"
                    :class="getGradeBadge(classGrade.statistics.overall_percentage).variant === 'destructive' ? 'text-white' : ''"
                  >
                    {{ getGradeBadge(classGrade.statistics.overall_percentage).label }}
                  </Badge>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <!-- Sorting Controls -->
              <div class="flex items-center gap-2 mb-4 pb-4 border-b">
                <Select v-model="sortField">
                  <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Sort by" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="date">Date</SelectItem>
                    <SelectItem value="title">Title</SelectItem>
                    <SelectItem value="percentage">Percentage</SelectItem>
                    <SelectItem value="type">Type</SelectItem>
                    <SelectItem value="score">Score</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" @click="toggleSortDirection" :title="sortDirection === 'asc' ? 'Ascending' : 'Descending'">
                  <ArrowUp v-if="sortDirection === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else class="h-4 w-4" />
                </Button>
              </div>
              <div class="space-y-2">
                <div
                  v-for="grade in sortGrades(classGrade.grades)"
                  :key="grade.id"
                  class="flex items-center justify-between p-3 border rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ getTypeIcon(grade.type) }}</span>
                    <div>
                      <div class="font-medium">{{ grade.title }}</div>
                      <div class="text-sm text-muted-foreground">
                        {{ formatDate(grade.graded_at) }}
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div :class="getGradeColor(grade.percentage)" class="font-semibold">
                      {{ grade.score }}/{{ grade.points }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                      {{ formatPercentage(grade.percentage) }}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent
          v-for="classGrade in props.class_grades"
          :key="classGrade.classlist.id"
          :value="classGrade.classlist.id"
          class="space-y-4"
        >
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>{{ classGrade.classlist.name }}</CardTitle>
                  <CardDescription>
                    AY {{ classGrade.classlist.academic_year }}
                    <span v-if="classGrade.classlist.section">
                      • {{ classGrade.classlist.section.name }}
                    </span>
                  </CardDescription>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold">
                    {{ formatPercentage(classGrade.statistics.overall_percentage) }}
                  </div>
                  <Badge
                    :variant="getGradeBadge(classGrade.statistics.overall_percentage).variant"
                    :class="getGradeBadge(classGrade.statistics.overall_percentage).variant === 'destructive' ? 'text-white' : ''"
                  >
                    {{ getGradeBadge(classGrade.statistics.overall_percentage).label }}
                  </Badge>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <!-- Statistics -->
              <div class="grid gap-4 md:grid-cols-4 mb-6">
                <div>
                  <div class="text-sm text-muted-foreground">Total Items</div>
                  <div class="text-2xl font-bold">{{ classGrade.statistics.total_items }}</div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Total Points</div>
                  <div class="text-2xl font-bold">
                    {{ classGrade.statistics.earned_points }}/{{ classGrade.statistics.total_points }}
                  </div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Average</div>
                  <div class="text-2xl font-bold">
                    {{ formatPercentage(classGrade.statistics.average_percentage) }}
                  </div>
                </div>
                <div>
                  <div class="text-sm text-muted-foreground">Overall</div>
                  <div class="text-2xl font-bold">
                    {{ formatPercentage(classGrade.statistics.overall_percentage) }}
                  </div>
                </div>
              </div>

              <!-- Grade History Chart -->
              <div v-if="classGrade.grade_history.length > 0" class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Grade History</h3>
                <div class="h-48">
                  <Line
                    :data="{
                      labels: classGrade.grade_history.map(g => formatDate(g.date)),
                      datasets: [
                        {
                          label: 'Grade Percentage',
                          data: classGrade.grade_history.map(g => g.percentage),
                          borderColor: 'rgb(59, 130, 246)',
                          backgroundColor: 'rgba(59, 130, 246, 0.1)',
                          tension: 0.4,
                        },
                      ],
                    }"
                    :options="{
                      responsive: true,
                      maintainAspectRatio: false,
                      scales: {
                        y: {
                          beginAtZero: true,
                          max: 100,
                        },
                      },
                    }"
                  />
                </div>
              </div>

              <!-- Sorting Controls -->
              <div class="flex items-center gap-2 mb-4 pb-4 border-b">
                <Select v-model="sortField">
                  <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Sort by" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="date">Date</SelectItem>
                    <SelectItem value="title">Title</SelectItem>
                    <SelectItem value="percentage">Percentage</SelectItem>
                    <SelectItem value="type">Type</SelectItem>
                    <SelectItem value="score">Score</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" @click="toggleSortDirection" :title="sortDirection === 'asc' ? 'Ascending' : 'Descending'">
                  <ArrowUp v-if="sortDirection === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else class="h-4 w-4" />
                </Button>
              </div>
              <!-- Grades List -->
              <div class="space-y-2">
                <div
                  v-for="grade in sortGrades(classGrade.grades)"
                  :key="grade.id"
                  class="flex items-center justify-between p-3 border rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ getTypeIcon(grade.type) }}</span>
                    <div>
                      <div class="font-medium">{{ grade.title }}</div>
                      <div class="text-sm text-muted-foreground">
                        {{ formatDate(grade.graded_at) }}
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div :class="getGradeColor(grade.percentage)" class="font-semibold">
                      {{ grade.score }}/{{ grade.points }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                      {{ formatPercentage(grade.percentage) }}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
