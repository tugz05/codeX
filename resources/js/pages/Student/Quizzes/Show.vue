<script setup lang="ts">
import { computed } from 'vue'
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Clock, Award, CheckCircle2, AlertCircle, FileText } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string }
  quiz: {
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    attempts_count: number
    can_attempt: boolean
    show_correct_answers: boolean
    start_date: string | null
    end_date: string | null
  }
  attempts: Array<{
    id: number
    attempt_number: number
    score: number
    percentage: number
    status: string
    submitted_at: string | null
  }>
}>()

function formatDate(value?: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleString()
}

function startQuiz() {
  router.post(route('student.quizzes.start', [props.classlist.id, props.quiz.id]))
}

const submittedAttempts = computed(() => {
  return props.attempts.filter(a => a.status === 'submitted').sort((a, b) => {
    const dateA = a.submitted_at ? new Date(a.submitted_at).getTime() : 0
    const dateB = b.submitted_at ? new Date(b.submitted_at).getTime() : 0
    return dateB - dateA // Latest first
  })
})
</script>

<template>
  <Head :title="`${props.quiz.title} · Quiz`" />

  <AuthLayoutStudent>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex items-center gap-3">
        <Link :href="route('student.quizzes.index', props.classlist.id)" as="button">
          <Button variant="outline" size="sm"><ArrowLeft class="mr-1 h-4 w-4" /> Back</Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-xl font-semibold">{{ props.quiz.title }}</h1>
          <p class="text-sm text-muted-foreground">{{ props.classlist.name }}</p>
        </div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div v-if="props.quiz.description" class="mb-4 text-sm text-muted-foreground">
          {{ props.quiz.description }}
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="flex items-center gap-2">
            <Award class="h-5 w-5 text-muted-foreground" />
            <div>
              <div class="text-sm font-medium">Total Points</div>
              <div class="text-xs text-muted-foreground">{{ props.quiz.total_points }} points</div>
            </div>
          </div>
          <div v-if="props.quiz.time_limit" class="flex items-center gap-2">
            <Clock class="h-5 w-5 text-muted-foreground" />
            <div>
              <div class="text-sm font-medium">Time Limit</div>
              <div class="text-xs text-muted-foreground">{{ props.quiz.time_limit }} minutes</div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <CheckCircle2 class="h-5 w-5 text-muted-foreground" />
            <div>
              <div class="text-sm font-medium">Attempts</div>
              <div class="text-xs text-muted-foreground">
                {{ props.quiz.attempts_count }}/{{ props.quiz.attempts_allowed }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="props.quiz.start_date || props.quiz.end_date" class="mt-4 text-xs text-muted-foreground">
          <div v-if="props.quiz.start_date">Available from: {{ formatDate(props.quiz.start_date) }}</div>
          <div v-if="props.quiz.end_date">Due: {{ formatDate(props.quiz.end_date) }}</div>
        </div>

        <div v-if="!props.quiz.can_attempt" class="mt-4 rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20">
          <div class="flex items-center gap-2 text-sm text-yellow-800 dark:text-yellow-300">
            <AlertCircle class="h-4 w-4" />
            <span>You have reached the maximum number of attempts.</span>
          </div>
        </div>

        <div v-if="props.attempts.length > 0" class="mt-6">
          <h3 class="mb-3 text-sm font-semibold">Previous Attempts</h3>
          <div class="space-y-2">
            <div
              v-for="attempt in props.attempts"
              :key="attempt.id"
              class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-accent/50"
            >
              <div class="flex-1">
                <div class="text-sm font-medium">Attempt {{ attempt.attempt_number }}</div>
                <div class="text-xs text-muted-foreground">
                  Score: {{ attempt.score }}/{{ props.quiz.total_points }}
                  ({{ attempt.percentage != null ? Number(attempt.percentage).toFixed(1) : '0.0' }}%)
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-xs text-muted-foreground">
                  {{ attempt.submitted_at ? formatDate(attempt.submitted_at) : 'In Progress' }}
                </div>
                <Link
                  v-if="attempt.status === 'submitted'"
                  :href="route('student.quizzes.result', [props.classlist.id, props.quiz.id, attempt.id])"
                  as="button"
                >
                  <Button variant="outline" size="sm">
                    <FileText class="mr-1 h-3 w-3" /> View Results
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Link
            v-if="submittedAttempts.length > 0"
            :href="route('student.quizzes.result', [props.classlist.id, props.quiz.id, submittedAttempts[0].id])"
            as="button"
          >
            <Button variant="outline" size="lg">
              <FileText class="mr-2 h-4 w-4" /> View Latest Results
            </Button>
          </Link>
          <Button
            v-if="props.quiz.can_attempt"
            @click="startQuiz"
            size="lg"
          >
            <CheckCircle2 class="mr-2 h-4 w-4" /> Start Quiz
          </Button>
        </div>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
