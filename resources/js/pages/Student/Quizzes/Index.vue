<script setup lang="ts">
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Clock, Award, CheckCircle2, FileText } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
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
  }>
}>()

function formatDate(value?: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleString()
}
</script>

<template>
  <Head :title="`Quizzes · ${props.classlist.name}`" />

  <AuthLayoutStudent>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex items-center gap-3">
        <Link :href="route('student.classlist')" as="button">
          <Button variant="outline" size="sm"><ArrowLeft class="mr-1 h-4 w-4" /> Back</Button>
        </Link>
        <div>
          <h1 class="text-xl font-semibold">Quizzes</h1>
          <p class="text-sm text-muted-foreground">
            {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
          </p>
        </div>
      </div>

      <div class="space-y-3">
        <div
          v-for="quiz in props.quizzes"
          :key="quiz.id"
          class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-2">
              <div class="flex items-center gap-2">
                <h3 class="text-base font-semibold">{{ quiz.title }}</h3>
                <span
                  v-if="quiz.latest_status === 'submitted'"
                  class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                >
                  Completed
                </span>
              </div>
              <p v-if="quiz.description" class="text-sm text-muted-foreground line-clamp-2">
                {{ quiz.description }}
              </p>
              <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                <span class="flex items-center gap-1">
                  <Award class="h-3 w-3" /> {{ quiz.total_points }} points
                </span>
                <span v-if="quiz.time_limit" class="flex items-center gap-1">
                  <Clock class="h-3 w-3" /> {{ quiz.time_limit }} minutes
                </span>
                <span>Attempts: {{ quiz.attempts_count }}/{{ quiz.attempts_allowed }}</span>
                <span v-if="quiz.latest_score !== null">
                  Best Score: {{ quiz.latest_score }}/{{ quiz.total_points }}
                  ({{ quiz.latest_percentage != null ? Number(quiz.latest_percentage).toFixed(1) : '0.0' }}%)
                </span>
              </div>
              <div v-if="quiz.start_date || quiz.end_date" class="text-xs text-muted-foreground">
                <span v-if="quiz.start_date">Available: {{ formatDate(quiz.start_date) }}</span>
                <span v-if="quiz.end_date"> • Due: {{ formatDate(quiz.end_date) }}</span>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <Link
                :href="route('student.quizzes.show', [props.classlist.id, quiz.id])"
                as="button"
              >
                <Button variant="outline" size="sm">View</Button>
              </Link>
              <Link
                v-if="quiz.latest_status === 'submitted' && quiz.latest_attempt_id"
                :href="route('student.quizzes.result', [props.classlist.id, quiz.id, quiz.latest_attempt_id])"
                as="button"
              >
                <Button variant="secondary" size="sm">
                  <FileText class="mr-1 h-4 w-4" /> View Results
                </Button>
              </Link>
              <Link
                v-if="quiz.can_attempt"
                :href="route('student.quizzes.start', [props.classlist.id, quiz.id])"
                as="button"
                method="post"
              >
                <Button size="sm">
                  <CheckCircle2 class="mr-1 h-4 w-4" /> Start
                </Button>
              </Link>
            </div>
          </div>
        </div>
        <div v-if="props.quizzes.length === 0" class="text-center py-8 text-muted-foreground">
          No quizzes available
        </div>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
