<script setup lang="ts">
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { 
  BookOpen, 
  Calendar, 
  Clock, 
  CheckCircle2, 
  XCircle, 
  AlertCircle,
  TrendingUp,
  FileText,
  ClipboardList,
  GraduationCap,
  Code2,
  ArrowRight,
  Award,
  BarChart3
} from 'lucide-vue-next'
import { computed } from 'vue'

interface Classlist {
  id: string
  name: string
  academic_year: string
  room: string
  section: { id: number; name: string } | null
  joined_at: string | null
}

interface Deadline {
  id: number
  type: 'activity' | 'assignment' | 'quiz' | 'examination'
  title: string
  classlist: { id: string; name: string }
  due_date: string | null
  due_time: string | null
  due_datetime: string | null
  points: number | null
  submitted: boolean
  status: string
}

interface Grade {
  id: number
  type: 'activity' | 'assignment' | 'quiz' | 'examination'
  title: string
  classlist: { id: string; name: string }
  score: number | null
  points: number | null
  percentage: number
  feedback: string | null
  submitted_at: string | null
  graded_at: string
}

interface PendingSubmission {
  id: number
  type: 'activity' | 'assignment'
  title: string
  classlist: { id: string; name: string }
  due_date: string | null
  due_time: string | null
  points: number | null
}

interface RecentActivity {
  id: number
  type: 'activity' | 'assignment'
  title: string
  classlist: { id: string; name: string }
  created_at: string
}

interface Statistics {
  totalClasses: number
  totalItems: number
  completedItems: number
  completionPercentage: number
  averageGrade: number | null
  upcomingDeadlinesCount: number
  pendingSubmissionsCount: number
}

const props = defineProps<{
  enrolledClasses: Classlist[]
  upcomingDeadlines: Deadline[]
  recentGrades: Grade[]
  pendingSubmissions: PendingSubmission[]
  recentActivities: RecentActivity[]
  statistics: Statistics
}>()

function formatDate(dateString: string | null): string {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatTime(timeString: string | null): string {
  if (!timeString) return ''
  return timeString
}

function formatDateTime(dateTimeString: string | null): string {
  if (!dateTimeString) return ''
  const date = new Date(dateTimeString)
  if (isNaN(date.getTime())) return ''
  return date.toLocaleString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    hour: 'numeric', 
    minute: '2-digit',
    hour12: true
  })
}

function getRelativeTime(dateTimeString: string | null): string {
  if (!dateTimeString) return ''
  const date = new Date(dateTimeString)
  const now = new Date()
  const diffMs = date.getTime() - now.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (diffDays < 0) return 'Overdue'
  if (diffDays === 0) {
    if (diffHours < 0) return 'Overdue'
    if (diffHours === 0) return 'Due soon'
    return `Due in ${diffHours} hour${diffHours !== 1 ? 's' : ''}`
  }
  if (diffDays === 1) return 'Due tomorrow'
  if (diffDays <= 7) return `Due in ${diffDays} days`
  return formatDate(dateTimeString)
}

function getTypeIcon(type: string) {
  switch (type) {
    case 'activity':
      return Code2
    case 'assignment':
      return FileText
    case 'quiz':
      return ClipboardList
    case 'examination':
      return GraduationCap
    default:
      return FileText
  }
}

function getTypeLabel(type: string): string {
  switch (type) {
    case 'activity':
      return 'Activity'
    case 'assignment':
      return 'Assignment'
    case 'quiz':
      return 'Quiz'
    case 'examination':
      return 'Examination'
    default:
      return type
  }
}

function getTypeRoute(type: string, id: number, classlistId: string): string {
  try {
    switch (type) {
      case 'activity':
        return route('student.activities.show', [classlistId, id])
      case 'assignment':
        return route('student.assignments.show', [classlistId, id])
      case 'quiz':
        return route('student.quizzes.show', [classlistId, id])
      case 'examination':
        return route('student.examinations.show', [classlistId, id])
      default:
        return '#'
    }
  } catch (e) {
    return '#'
  }
}

function formatPercentage(value: number | null | undefined | string): string {
  if (value === null || value === undefined || value === '') return 'N/A'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return 'N/A'
  return num.toFixed(1) + '%'
}

function getGradeColor(percentage: number | null | undefined | string): string {
  const num = typeof percentage === 'string' ? parseFloat(percentage) : (percentage ?? 0)
  if (isNaN(num)) return 'text-muted-foreground'
  if (num >= 90) return 'text-green-600'
  if (num >= 80) return 'text-blue-600'
  if (num >= 70) return 'text-yellow-600'
  if (num >= 60) return 'text-orange-600'
  return 'text-red-600'
}

function getGradeBadgeVariant(percentage: number | null | undefined | string): 'default' | 'secondary' | 'destructive' | 'outline' {
  const num = typeof percentage === 'string' ? parseFloat(percentage) : (percentage ?? 0)
  if (isNaN(num)) return 'outline'
  if (num >= 90) return 'default'
  if (num >= 70) return 'secondary'
  return 'destructive'
}
</script>

<template>
  <AuthLayoutStudent>
    <Head title="Dashboard" />
    
    <div class="flex-1 space-y-4 sm:space-y-6 p-3 sm:p-4 md:p-6 lg:p-8 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p class="text-muted-foreground mt-2">
          Overview of your classes, deadlines, and progress
        </p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Enrolled Classes</CardTitle>
            <BookOpen class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statistics.totalClasses }}</div>
            <p class="text-xs text-muted-foreground">Active classes</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Completion Rate</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statistics.completionPercentage }}%</div>
            <p class="text-xs text-muted-foreground">
              {{ statistics.completedItems }} of {{ statistics.totalItems }} completed
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Grade</CardTitle>
            <Award class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ statistics.averageGrade !== null ? statistics.averageGrade.toFixed(1) + '%' : 'N/A' }}
            </div>
            <p class="text-xs text-muted-foreground">Based on graded items</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Upcoming Deadlines</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ statistics.upcomingDeadlinesCount }}</div>
            <p class="text-xs text-muted-foreground">Next 7 days</p>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Grid -->
      <div class="grid gap-4 sm:gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <!-- Enrolled Classes -->
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>My Classes</CardTitle>
            <CardDescription>Enrolled classes</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <div v-if="enrolledClasses.length === 0" class="text-center py-8 text-muted-foreground">
              <BookOpen class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No classes enrolled</p>
            </div>
            <Link
              v-for="classItem in enrolledClasses.slice(0, 5)"
              :key="classItem.id"
              :href="route('student.activities.index', classItem.id)"
              class="block p-3 rounded-lg border hover:bg-accent transition-colors"
            >
              <div class="font-medium">{{ classItem.name }}</div>
              <div class="text-sm text-muted-foreground mt-1">
                {{ classItem.room }} • {{ classItem.academic_year }}
              </div>
            </Link>
            <Button
              v-if="enrolledClasses.length > 5"
              variant="outline"
              class="w-full mt-2"
              :href="route('student.classlist')"
              as-child
            >
              <Link>View All Classes</Link>
            </Button>
          </CardContent>
        </Card>

        <!-- Upcoming Deadlines -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Upcoming Deadlines</CardTitle>
            <CardDescription>Due in the next 7 days</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="upcomingDeadlines.length === 0" class="text-center py-8 text-muted-foreground">
              <Calendar class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No upcoming deadlines</p>
            </div>
            <div v-else class="space-y-3">
              <Link
                v-for="deadline in upcomingDeadlines"
                :key="`${deadline.type}-${deadline.id}`"
                :href="getTypeRoute(deadline.type, deadline.id, deadline.classlist.id)"
                class="flex items-start gap-4 p-4 rounded-lg border hover:bg-accent transition-colors"
              >
                <component :is="getTypeIcon(deadline.type)" class="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                      <div class="font-medium">{{ deadline.title }}</div>
                      <div class="text-sm text-muted-foreground mt-1">
                        {{ deadline.classlist.name }} • {{ getTypeLabel(deadline.type) }}
                      </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                      <Badge :variant="deadline.submitted ? 'default' : 'secondary'">
                        {{ deadline.submitted ? 'Submitted' : 'Pending' }}
                      </Badge>
                    </div>
                  </div>
                  <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                    <div class="flex items-center gap-1">
                      <Clock class="h-4 w-4" />
                      <span>{{ getRelativeTime(deadline.due_datetime) }}</span>
                    </div>
                    <div v-if="deadline.points" class="flex items-center gap-1">
                      <span>{{ deadline.points }} points</span>
                    </div>
                  </div>
                </div>
              </Link>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Grades -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Recent Grades</CardTitle>
            <CardDescription>Latest feedback and scores</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="recentGrades.length === 0" class="text-center py-8 text-muted-foreground">
              <Award class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No grades yet</p>
            </div>
            <div v-else class="space-y-3">
              <Link
                v-for="grade in recentGrades"
                :key="`${grade.type}-${grade.id}`"
                :href="getTypeRoute(grade.type, grade.id, grade.classlist.id)"
                class="flex items-start gap-4 p-4 rounded-lg border hover:bg-accent transition-colors"
              >
                <component :is="getTypeIcon(grade.type)" class="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                      <div class="font-medium">{{ grade.title }}</div>
                      <div class="text-sm text-muted-foreground mt-1">
                        {{ grade.classlist.name }} • {{ getTypeLabel(grade.type) }}
                      </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                      <Badge :variant="getGradeBadgeVariant(grade.percentage)" class="text-sm font-semibold">
                        {{ formatPercentage(grade.percentage) }}
                      </Badge>
                    </div>
                  </div>
                  <div class="flex items-center gap-4 mt-2 text-sm">
                    <span :class="getGradeColor(grade.percentage)" class="font-medium">
                      {{ grade.score }}/{{ grade.points }} points
                    </span>
                    <span class="text-muted-foreground">
                      {{ formatDate(grade.graded_at) }}
                    </span>
                  </div>
                  <div v-if="grade.feedback" class="mt-2 text-sm text-muted-foreground line-clamp-2">
                    {{ grade.feedback }}
                  </div>
                </div>
              </Link>
            </div>
          </CardContent>
        </Card>

        <!-- Pending Submissions -->
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>Pending Submissions</CardTitle>
            <CardDescription>Items to complete</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="pendingSubmissions.length === 0" class="text-center py-8 text-muted-foreground">
              <CheckCircle2 class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>All caught up!</p>
            </div>
            <div v-else class="space-y-3">
              <Link
                v-for="item in pendingSubmissions"
                :key="`${item.type}-${item.id}`"
                :href="getTypeRoute(item.type, item.id, item.classlist.id)"
                class="flex items-start gap-3 p-3 rounded-lg border hover:bg-accent transition-colors"
              >
                <component :is="getTypeIcon(item.type)" class="h-4 w-4 mt-0.5 text-muted-foreground" />
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">{{ item.title }}</div>
                  <div class="text-xs text-muted-foreground mt-1">
                    {{ item.classlist.name }}
                  </div>
                  <div v-if="item.due_date" class="text-xs text-muted-foreground mt-1">
                    Due: {{ formatDate(item.due_date) }}
                    <span v-if="item.due_time"> at {{ formatTime(item.due_time) }}</span>
                  </div>
                </div>
              </Link>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Activities -->
        <Card class="lg:col-span-3">
          <CardHeader>
            <CardTitle>Recent Activities</CardTitle>
            <CardDescription>Latest items from your classes</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="recentActivities.length === 0" class="text-center py-8 text-muted-foreground">
              <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
              <p>No recent activities</p>
            </div>
            <div v-else class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
              <Link
                v-for="activity in recentActivities"
                :key="`${activity.type}-${activity.id}`"
                :href="getTypeRoute(activity.type, activity.id, activity.classlist.id)"
                class="flex items-start gap-3 p-4 rounded-lg border hover:bg-accent transition-colors"
              >
                <component :is="getTypeIcon(activity.type)" class="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div class="flex-1 min-w-0">
                  <div class="font-medium">{{ activity.title }}</div>
                  <div class="text-sm text-muted-foreground mt-1">
                    {{ activity.classlist.name }} • {{ getTypeLabel(activity.type) }}
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    {{ formatDate(activity.created_at) }}
                  </div>
                </div>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
