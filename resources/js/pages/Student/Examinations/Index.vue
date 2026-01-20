<script setup lang="ts">
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Clock, Award, CheckCircle2, GraduationCap, Shield, FileText } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
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
  }>
}>()

function formatDate(value?: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleString()
}
</script>

<template>
  <Head :title="`Examinations · ${props.classlist.name}`" />

  <AuthLayoutStudent>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex items-center gap-3">
        <Link :href="route('student.classlist')" as="button">
          <Button variant="outline" size="sm"><ArrowLeft class="mr-1 h-4 w-4" /> Back</Button>
        </Link>
        <div>
          <h1 class="text-xl font-semibold">Examinations</h1>
          <p class="text-sm text-muted-foreground">
            {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
          </p>
        </div>
      </div>

      <div class="space-y-3">
        <div
          v-for="exam in props.examinations"
          :key="exam.id"
          class="rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-accent/50"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                  <GraduationCap class="inline h-3 w-3 mr-1" /> Examination
                </span>
                <h3 class="text-base font-semibold">{{ exam.title }}</h3>
                <Badge v-if="exam.require_proctoring" variant="outline" class="text-xs">
                  <Shield class="inline h-3 w-3 mr-1" /> Proctored
                </Badge>
              </div>
              <p v-if="exam.description" class="text-sm text-muted-foreground line-clamp-2 mb-2">
                {{ exam.description }}
              </p>
              <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                <span>{{ exam.total_points }} points</span>
                <span v-if="exam.time_limit">{{ exam.time_limit }} minutes</span>
                <span>{{ exam.attempts_count }}/{{ exam.attempts_allowed }} attempts</span>
                <span v-if="exam.latest_score !== null">
                  Latest: {{ exam.latest_score }}/{{ exam.total_points }} ({{ exam.latest_percentage != null ? Number(exam.latest_percentage).toFixed(1) : '0.0' }}%)
                </span>
              </div>
              <div v-if="exam.start_date || exam.end_date" class="mt-2 text-xs text-muted-foreground">
                <span v-if="exam.start_date">From: {{ formatDate(exam.start_date) }}</span>
                <span v-if="exam.end_date" class="ml-2">Until: {{ formatDate(exam.end_date) }}</span>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <Link :href="route('student.examinations.show', [props.classlist.id, exam.id])" as="button">
                <Button variant="outline" size="sm">
                  View
                </Button>
              </Link>
              <Link
                v-if="exam.latest_status === 'submitted' && exam.latest_attempt_id"
                :href="route('student.examinations.result', [props.classlist.id, exam.id, exam.latest_attempt_id])"
                as="button"
              >
                <Button variant="secondary" size="sm">
                  <FileText class="mr-1 h-4 w-4" /> View Results
                </Button>
              </Link>
            </div>
          </div>
        </div>

        <div v-if="props.examinations.length === 0" class="rounded-xl border bg-card p-8 text-center">
          <GraduationCap class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <p class="text-sm text-muted-foreground">No examinations available at this time.</p>
        </div>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
