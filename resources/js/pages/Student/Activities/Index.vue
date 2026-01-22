<script setup lang="ts">
import { computed, ref } from 'vue'
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import ClassMessagesPanel from '@/components/ClassMessagesPanel.vue'
import { ArrowLeft, ClipboardList, GraduationCap, FileText, Award, Clock, CheckCircle2, Shield, BookOpen, Eye, NotebookPen, MessageSquare } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  activities: Array<{
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    created_at: string
    status?: 'Draft' | 'Submitted' | 'Graded' | 'Missing'
    score?: number | null
    submitted_at?: string | null
    attachments: Array<{ id:number; name:string; url:string; type:string | null }>
  }>
  quizzes: Array<{
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    attempts_count: number
    can_attempt: boolean
    latest_score: number | null
    latest_percentage: number | null
    latest_status: string | null
    latest_attempt_id: number | null
    start_date: string | null
    end_date: string | null
    created_at: string
  }>
  examinations: Array<{
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    attempts_count: number
    can_attempt: boolean
    latest_score: number | null
    latest_percentage: number | null
    latest_status: string | null
    latest_attempt_id: number | null
    require_proctoring: boolean
    start_date: string | null
    end_date: string | null
    created_at: string
  }>
  assignments?: Array<{
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments_count: number
    author: {
      id: number
      name: string
    }
  }>
  materials?: Array<{
    id: number
    title: string
    description: string | null
    type: string
    link_url: string | null
    video_url: string | null
    embed_code: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments_count: number
    author: {
      id: number
      name: string
    }
  }>
}>()

const showMessages = ref(false)

function formatDate(value?: string | null) {
  if (!value) return ''
  const d = new Date(value)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleString()
}

function formatTime(value?: string | null) {
  if (!value) return ''
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
    const [hh, mm] = value.split(':')
    const d = new Date()
    d.setHours(Number(hh), Number(mm), 0, 0)
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  }
  const d = new Date(value)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

function statusBadgeClass(status?: string) {
  switch (status) {
    case 'Graded':
      return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    case 'Submitted':
      return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
    case 'Draft':
      return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300'
    case 'Missing':
      return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-neutral-300'
  }
}

// Combine all items and sort by created_at
const allItems = computed(() => {
  const items: Array<{
    id: number
    type: 'assignment' | 'activity' | 'quiz' | 'examination' | 'material'
    title: string
    description: string | null
    instruction?: string | null
    created_at: string
    [key: string]: any
  }> = []

  // Add assignments
  ;(props.assignments || []).forEach(assignment => {
    items.push({
      ...assignment,
      type: 'assignment' as const,
      description: assignment.instruction || null,
    })
  })

  // Add activities
  ;(props.activities || []).forEach(activity => {
    items.push({
      ...activity,
      type: 'activity' as const,
    })
  })

  // Add quizzes
  ;(props.quizzes || []).forEach(quiz => {
    items.push({
      ...quiz,
      type: 'quiz' as const,
    })
  })

  // Add examinations
  ;(props.examinations || []).forEach(exam => {
    items.push({
      ...exam,
      type: 'examination' as const,
    })
  })

  // Add materials
  ;(props.materials || []).forEach(material => {
    items.push({
      ...material,
      type: 'material' as const,
    })
  })

  // Sort by created_at (newest first)
  return items.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

function isAssignmentAccessible(assignment: any): boolean {
  if (!assignment.accessible_date) return true
  
  const now = new Date()
  const accessibleDate = new Date(assignment.accessible_date)
  
  if (assignment.accessible_time) {
    const [hours, minutes] = assignment.accessible_time.split(':')
    accessibleDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    accessibleDate.setHours(0, 0, 0, 0)
  }
  
  return now >= accessibleDate
}

function isMaterialAccessible(material: any): boolean {
  if (!material.accessible_date) return true
  
  const now = new Date()
  const accessibleDate = new Date(material.accessible_date)
  
  if (material.accessible_time) {
    const [hours, minutes] = material.accessible_time.split(':')
    accessibleDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    accessibleDate.setHours(0, 0, 0, 0)
  }
  
  return now >= accessibleDate
}

function startQuiz(quizId: number) {
  router.post(route('student.quizzes.start', [props.classlist.id, quizId]))
}

function startExamination(examId: number) {
  router.post(route('student.examinations.start', [props.classlist.id, examId]))
}
</script>

<template>
  <Head :title="`Class Content · ${props.classlist.name}`" />

  <AuthLayoutStudent>
    <div class="flex h-full flex-1 flex-col gap-3 sm:gap-4 rounded-xl p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-wrap items-center gap-3">
        <Link :href="route('student.classlist')" as="button">
          <Button variant="outline" size="sm"><ArrowLeft class="mr-1 h-4 w-4" /> Back</Button>
        </Link>
        <div class="min-w-0">
          <h1 class="text-xl font-semibold">Class Content</h1>
          <p class="text-sm text-muted-foreground">
            {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
          </p>
        </div>
        <div class="ml-auto">
          <Button variant="outline" size="sm" @click="showMessages = true">
            <MessageSquare class="mr-1 h-4 w-4" /> Message Instructor
          </Button>
        </div>
      </div>

      <Dialog v-model:open="showMessages">
        <DialogContent class="!w-[96vw] !max-w-[1200px] !max-h-[90vh] overflow-hidden">
          <DialogHeader>
            <DialogTitle>Class Messages</DialogTitle>
          </DialogHeader>
          <div class="max-h-[75vh] overflow-y-auto">
            <ClassMessagesPanel :classlist-id="props.classlist.id" mode="student" />
          </div>
        </DialogContent>
      </Dialog>

      <!-- Unified List -->
      <div class="space-y-3">
        <template v-for="item in allItems" :key="`${item.type}-${item.id}`">
          <!-- Assignment Card -->
          <Link
            v-if="item.type === 'assignment'"
            :href="route('student.assignments.show', [props.classlist.id, item.id])"
            class="block rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                    <NotebookPen class="inline h-3 w-3 mr-1" /> Assignment
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="!item.accessible_date"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available
                  </span>
                  <span
                    v-else-if="isAssignmentAccessible(item)"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available Now
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
                  >
                    Scheduled
                  </span>
                </div>
                <p v-if="item.instruction" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                  {{ item.instruction }}
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                  <span v-if="item.points !== null">{{ item.points }} points</span>
                  <span v-if="item.due_date">
                    Due: {{ formatDate(item.due_date) }}
                    <span v-if="item.due_time"> at {{ formatTime(item.due_time) }}</span>
                  </span>
                  <span v-if="item.attachments_count">{{ item.attachments_count }} attachment{{ item.attachments_count !== 1 ? 's' : '' }}</span>
                  <span v-if="item.author">By: {{ item.author.name }}</span>
                  <span>Posted: {{ formatDate(item.created_at) }}</span>
                </div>
              </div>
            </div>
          </Link>

          <!-- Activity Card -->
          <Link
            v-else-if="item.type === 'activity'"
            :href="route('student.activities.show', [props.classlist.id, item.id])"
            class="block rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <FileText class="inline h-3 w-3 mr-1" /> Activity
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="item.status"
                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                    :class="statusBadgeClass(item.status)"
                  >
                    {{ item.status }}
                  </span>
                </div>
                <p v-if="item.instruction" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                  {{ item.instruction }}
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                  <span v-if="item.points !== null">{{ item.points }} points</span>
                  <span v-if="item.due_date">
                    Due: {{ formatDate(item.due_date) }}
                    <span v-if="item.due_time"> at {{ formatTime(item.due_time) }}</span>
                  </span>
                  <span v-if="item.status === 'Graded' && item.points !== null && item.score !== null">
                    Score: {{ item.score }}/{{ item.points }}
                  </span>
                  <span>Posted: {{ formatDate(item.created_at) }}</span>
                </div>
                <div v-if="item.attachments?.length" class="mt-2 flex flex-wrap gap-2">
                  <span
                    v-for="f in item.attachments"
                    :key="f.id"
                    class="inline-flex items-center rounded-md border px-2 py-1 text-xs"
                  >
                    {{ f.name }}
                  </span>
                </div>
              </div>
            </div>
          </Link>

          <!-- Quiz Card -->
          <div
            v-else-if="item.type === 'quiz'"
            class="rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <ClipboardList class="inline h-3 w-3 mr-1" /> Quiz
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="item.latest_status === 'submitted'"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Completed
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                  {{ item.description }}
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                  <span>{{ item.total_points }} points</span>
                  <span v-if="item.time_limit">{{ item.time_limit }} minutes</span>
                  <span>{{ item.attempts_count }}/{{ item.attempts_allowed }} attempts</span>
                  <span v-if="item.latest_score !== null">
                    Best: {{ item.latest_score }}/{{ item.total_points }} ({{ item.latest_percentage != null ? Number(item.latest_percentage).toFixed(1) : '0.0' }}%)
                  </span>
                  <span v-if="item.start_date || item.end_date">
                    <span v-if="item.start_date">From: {{ formatDate(item.start_date) }}</span>
                    <span v-if="item.end_date"> • Until: {{ formatDate(item.end_date) }}</span>
                  </span>
                </div>
              </div>
              <div class="flex flex-col gap-2">
                <Link
                  v-if="item.latest_status === 'submitted' && item.latest_attempt_id"
                  :href="route('student.quizzes.result', [props.classlist.id, item.id, item.latest_attempt_id])"
                  as="button"
                >
                  <Button variant="secondary" size="sm">
                    <FileText class="mr-1 h-4 w-4" /> View Results
                  </Button>
                </Link>
                <Button
                  v-if="item.can_attempt"
                  size="sm"
                  @click="startQuiz(item.id)"
                >
                  <CheckCircle2 class="mr-1 h-4 w-4" /> Start
                </Button>
              </div>
            </div>
          </div>

          <!-- Examination Card -->
          <div
            v-else-if="item.type === 'examination'"
            class="rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                    <GraduationCap class="inline h-3 w-3 mr-1" /> Examination
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <Badge v-if="item.require_proctoring" variant="outline" class="text-xs">
                    <Shield class="inline h-3 w-3 mr-1" /> Proctored
                  </Badge>
                  <span
                    v-if="item.latest_status === 'submitted'"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Completed
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                  {{ item.description }}
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                  <span>{{ item.total_points }} points</span>
                  <span v-if="item.time_limit">{{ item.time_limit }} minutes</span>
                  <span>{{ item.attempts_count }}/{{ item.attempts_allowed }} attempts</span>
                  <span v-if="item.latest_score !== null">
                    Best: {{ item.latest_score }}/{{ item.total_points }} ({{ item.latest_percentage != null ? Number(item.latest_percentage).toFixed(1) : '0.0' }}%)
                  </span>
                  <span v-if="item.start_date || item.end_date">
                    <span v-if="item.start_date">From: {{ formatDate(item.start_date) }}</span>
                    <span v-if="item.end_date"> • Until: {{ formatDate(item.end_date) }}</span>
                  </span>
                </div>
              </div>
              <div class="flex flex-col gap-2">
                <Link
                  v-if="item.latest_status === 'submitted' && item.latest_attempt_id"
                  :href="route('student.examinations.result', [props.classlist.id, item.id, item.latest_attempt_id])"
                  as="button"
                >
                  <Button variant="secondary" size="sm">
                    <FileText class="mr-1 h-4 w-4" /> View Results
                  </Button>
                </Link>
                <Button
                  v-if="item.can_attempt"
                  size="sm"
                  @click="startExamination(item.id)"
                >
                  <CheckCircle2 class="mr-1 h-4 w-4" /> Start
                </Button>
              </div>
            </div>
          </div>

          <!-- Material Card -->
          <Link
            v-else-if="item.type === 'material'"
            :href="route('student.materials.show', [props.classlist.id, item.id])"
            class="block rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <BookOpen class="inline h-3 w-3 mr-1" /> Material
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="!item.accessible_date"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available
                  </span>
                  <span
                    v-else-if="isMaterialAccessible(item)"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available Now
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
                  >
                    Scheduled
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                  {{ item.description }}
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                  <span class="capitalize">{{ item.type }}</span>
                  <span v-if="item.attachments_count">{{ item.attachments_count }} attachment{{ item.attachments_count !== 1 ? 's' : '' }}</span>
                  <span v-if="item.author">By: {{ item.author.name }}</span>
                  <span>Posted: {{ formatDate(item.created_at) }}</span>
                </div>
              </div>
            </div>
          </Link>
        </template>

        <div v-if="allItems.length === 0" class="rounded-xl border p-6 text-center text-sm text-muted-foreground">
          No content available for this class yet.
        </div>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
