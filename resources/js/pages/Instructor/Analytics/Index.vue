<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { TrendingUp, Users, Award, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js'
import { Line, Bar } from 'vue-chartjs'
import axios from 'axios'

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
)

// Data types
interface AnalyticsData {
  submissionTrends: Array<{
    date: string
    count: number
  }>
  classPerformance: Array<{
    name: string
    students_count: number
    average_score: number
  }>
  studentPerformance: Record<string, number>
  activityCompletionRates: Array<{
    activity_id: number
    completion_rate: number
  }>
}

const loading = ref(true)
const error = ref<string | null>(null)
const selectedTimeRange = ref('7d') // '7d', '30d', '90d'

const submissionData = ref<ChartData>({
  labels: [],
  datasets: []
})

const classPerformanceData = ref<ChartData>({
  labels: [],
  datasets: []
})

const studentDistributionData = ref<ChartData>({
  labels: [],
  datasets: []
})

const activityCompletionData = ref<ChartData>({
  labels: [],
  datasets: []
})

// Summary metrics
const metrics = ref({
  totalSubmissions: 0,
  averageScore: 0,
  activeStudents: 0,
  completionRate: 0
})

// Computed properties for formatted metrics
const formattedMetrics = computed(() => ({
  totalSubmissions: metrics.value.totalSubmissions.toLocaleString(),
  averageScore: `${metrics.value.averageScore.toFixed(1)}%`,
  activeStudents: metrics.value.activeStudents.toLocaleString(),
  completionRate: `${metrics.value.completionRate.toFixed(1)}%`
}))

const lineChartOptions: ChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        boxWidth: 6,
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 13,
      },
      bodyFont: {
        size: 12,
      },
    }
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        maxRotation: 0,
        autoSkipPadding: 20,
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
      border: {
        dash: [4, 4],
      }
    }
  }
}

const barChartOptions: ChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 13,
      },
      bodyFont: {
        size: 12,
      },
    }
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        maxRotation: 0,
        autoSkipPadding: 20,
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
      border: {
        dash: [4, 4],
      }
    }
  }
}

// Load analytics data
async function loadAnalytics() {
  loading.value = true
  error.value = null

  try {
    const { data } = await axios.get<AnalyticsData>(
      route('instructor.analytics.data', { timeRange: selectedTimeRange.value })
    )

    // Calculate summary metrics
    metrics.value = {
      totalSubmissions: data.submissionTrends.reduce((sum, t) => sum + t.count, 0),
      averageScore: data.classPerformance.reduce((sum, c) => sum + c.average_score, 0) / data.classPerformance.length,
      activeStudents: Object.values(data.studentPerformance).reduce((sum, count) => sum + count, 0),
      completionRate: data.activityCompletionRates.reduce((sum, a) => sum + a.completion_rate, 0) / data.activityCompletionRates.length
    }

    // Submission trends chart with gradient
    const ctx = document.createElement('canvas').getContext('2d')
    const gradient = ctx?.createLinearGradient(0, 0, 0, 400)
    gradient?.addColorStop(0, 'rgba(59, 130, 246, 0.5)')
    gradient?.addColorStop(1, 'rgba(59, 130, 246, 0)')

    submissionData.value = {
      labels: data.submissionTrends.map(t => new Date(t.date).toLocaleDateString()),
      datasets: [{
        label: 'Daily Submissions',
        data: data.submissionTrends.map(t => t.count),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: gradient || 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true
      }]
    }

    // Class performance chart
    classPerformanceData.value = {
      labels: data.classPerformance.map(c => c.name),
      datasets: [{
        label: 'Average Score',
        data: data.classPerformance.map(c => c.average_score),
        backgroundColor: 'rgba(99, 102, 241, 0.8)'
      }]
    }

    // Student distribution chart
    studentDistributionData.value = {
      labels: Object.keys(data.studentPerformance),
      datasets: [{
        label: 'Number of Students',
        data: Object.values(data.studentPerformance),
        backgroundColor: [
          'rgba(34, 197, 94, 0.8)',  // Success
          'rgba(59, 130, 246, 0.8)', // Primary
          'rgba(245, 158, 11, 0.8)', // Warning
          'rgba(239, 68, 68, 0.8)',  // Danger
          'rgba(161, 161, 170, 0.8)' // Muted
        ]
      }]
    }

    // Activity completion chart
    activityCompletionData.value = {
      labels: data.activityCompletionRates.map(a => `Activity ${a.activity_id}`),
      datasets: [{
        label: 'Completion Rate',
        data: data.activityCompletionRates.map(a => a.completion_rate),
        backgroundColor: 'rgba(99, 102, 241, 0.8)'
      }]
    }

    loading.value = false
  } catch (err) {
    error.value = 'Failed to load analytics data. Please try again.'
    loading.value = false
    console.error('Failed to load analytics:', err)
  }
}

onMounted(() => {
  loadAnalytics()
})
</script>

<template>
  <Head title="Analytics" />

  <AppLayout>
    <main class="min-h-[calc(100vh-4rem)] overflow-y-auto bg-gray-50/50 pb-8">
      <div class="sticky top-0 z-10 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto w-full px-6 py-4 md:px-8 lg:px-12">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Analytics Dashboard</h1>
              <p class="mt-1 text-sm text-muted-foreground sm:text-base">Comprehensive insights into student performance and activity.</p>
            </div>

            <div class="flex items-center gap-2">
              <select
                v-model="selectedTimeRange"
                class="rounded-md border border-input bg-background px-2 py-1.5 text-sm ring-offset-background sm:px-3 sm:py-2"
                @change="loadAnalytics"
              >
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
                <option value="90d">Last 90 Days</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Alert -->
      <Alert v-if="error" variant="destructive" class="mx-6 mt-6 md:mx-8 lg:mx-12">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <!-- Key Metrics -->
      <div class="mx-auto w-full px-6 md:px-8 lg:px-12">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <Card class="border-0 bg-gradient-to-br from-blue-500/10 to-blue-600/5">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Total Submissions</CardTitle>
              <TrendingUp class="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div v-if="loading" class="space-y-2">
                <Skeleton class="h-8 w-[100px]" />
              </div>
              <div v-else>
                <div class="text-2xl font-bold text-blue-600">{{ formattedMetrics.totalSubmissions }}</div>
                <p class="text-xs text-muted-foreground">Total student submissions</p>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 bg-gradient-to-br from-violet-500/10 to-violet-600/5">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Average Score</CardTitle>
              <Award class="h-4 w-4 text-violet-500" />
            </CardHeader>
            <CardContent>
              <div v-if="loading" class="space-y-2">
                <Skeleton class="h-8 w-[100px]" />
              </div>
              <div v-else>
                <div class="text-2xl font-bold text-violet-600">{{ formattedMetrics.averageScore }}</div>
                <p class="text-xs text-muted-foreground">Across all classes</p>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 bg-gradient-to-br from-emerald-500/10 to-emerald-600/5">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Active Students</CardTitle>
              <Users class="h-4 w-4 text-emerald-500" />
            </CardHeader>
            <CardContent>
              <div v-if="loading" class="space-y-2">
                <Skeleton class="h-8 w-[100px]" />
              </div>
              <div v-else>
                <div class="text-2xl font-bold text-emerald-600">{{ formattedMetrics.activeStudents }}</div>
                <p class="text-xs text-muted-foreground">Students with submissions</p>
              </div>
            </CardContent>
          </Card>

          <Card class="border-0 bg-gradient-to-br from-orange-500/10 to-orange-600/5">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Completion Rate</CardTitle>
              <CheckCircle2 class="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent>
              <div v-if="loading" class="space-y-2">
                <Skeleton class="h-8 w-[100px]" />
              </div>
              <div v-else>
                <div class="text-2xl font-bold text-orange-600">{{ formattedMetrics.completionRate }}</div>
                <p class="text-xs text-muted-foreground">Average activity completion</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="mx-auto mt-4 grid w-full gap-4 px-6 md:mt-6 md:gap-6 md:px-8 lg:px-12">
        <!-- Submission Trends -->
        <Card class="col-span-full border-0 bg-gradient-to-br from-card/50 to-card shadow-md lg:col-span-2">
          <CardHeader>
            <CardTitle>Submission Trends</CardTitle>
            <CardDescription>Daily submission activity over time</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loading" class="flex h-[350px] items-center justify-center sm:h-[400px]">
              <Skeleton class="h-[300px] w-full sm:h-[350px]" />
            </div>
            <div v-else class="h-[350px] sm:h-[400px]">
              <Line
                v-if="submissionData.labels.length"
                :data="submissionData"
                :options="{
                  ...lineChartOptions,
                  maintainAspectRatio: false,
                  responsive: true
                }"
              />
              <p v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                No submission data available for the selected period
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Class Performance -->
        <Card class="col-span-full border-0 bg-gradient-to-br from-card/50 to-card shadow-md lg:col-span-2">
          <CardHeader>
            <CardTitle>Class Performance</CardTitle>
            <CardDescription>Average scores by class section</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loading" class="flex h-[350px] items-center justify-center sm:h-[400px]">
              <Skeleton class="h-[300px] w-full sm:h-[350px]" />
            </div>
            <div v-else class="h-[350px] sm:h-[400px]">
              <Bar
                v-if="classPerformanceData.labels.length"
                :data="classPerformanceData"
                :options="{
                  ...barChartOptions,
                  maintainAspectRatio: false,
                  responsive: true
                }"
              />
              <p v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                No performance data available
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Student Distribution -->
        <Card class="col-span-full border-0 bg-gradient-to-br from-card/50 to-card shadow-md md:col-span-1">
          <CardHeader>
            <CardTitle>Grade Distribution</CardTitle>
            <CardDescription>Performance breakdown across all classes</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loading" class="flex h-[300px] items-center justify-center sm:h-[350px]">
              <Skeleton class="h-[250px] w-full sm:h-[300px]" />
            </div>
            <div v-else class="h-[300px] sm:h-[350px]">
              <Bar
                v-if="studentDistributionData.labels.length"
                :data="studentDistributionData"
                :options="{
                  ...barChartOptions,
                  maintainAspectRatio: false,
                  responsive: true,
                  indexAxis: 'y'
                }"
              />
              <p v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                No distribution data available
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Activity Completion -->
        <Card class="col-span-full border-0 bg-gradient-to-br from-card/50 to-card shadow-md md:col-span-1">
          <CardHeader>
            <CardTitle>Activity Completion Rates</CardTitle>
            <CardDescription>Student completion percentage by activity</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loading" class="flex h-[300px] items-center justify-center sm:h-[350px]">
              <Skeleton class="h-[250px] w-full sm:h-[300px]" />
            </div>
            <div v-else class="h-[300px] sm:h-[350px]">
              <Bar
                v-if="activityCompletionData.labels.length"
                :data="activityCompletionData"
                :options="{
                  ...barChartOptions,
                  maintainAspectRatio: false,
                  responsive: true,
                  indexAxis: 'y'
                }"
              />
              <p v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                No completion data available
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </main>
  </AppLayout>
</template>
