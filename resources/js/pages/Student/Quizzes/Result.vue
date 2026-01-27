<script setup lang="ts">
import { computed } from 'vue'
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Award, CheckCircle2, XCircle, FileText, Clock, Calendar, TrendingUp, AlertCircle, Shield, Eye, Copy, MousePointer2 } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string }
  quiz: {
    id: number
    title: string
    show_correct_answers: boolean
  }
  attempt: {
    id: number
    score: number
    total_points: number
    percentage: number
    submitted_at: string
    time_spent: number | null
  }
  tests: Array<{
    id: number
    title: string
    type: string | null
    description: string | null
    order: number
    answers: Array<{
      question_id: number
      question_text: string
      question_type: string
      question_points: number
      question_options: string[]
      correct_answer: string | string[]
      explanation: string | null
      user_answer: string[]
      is_correct: boolean
      points_earned: number
    }>
  }>
  activities?: Array<{
    id: number
    activity_type: string
    description: string | null
    metadata: Record<string, any> | null
    occurred_at: string
  }>
}>()

// Default empty array if activities not provided
const activities = computed(() => props.activities || [])

function formatTime(seconds: number | null): string {
  if (!seconds) return 'N/A'
  const minutes = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

function formatDate(value: string): string {
  return new Date(value).toLocaleString()
}

const percentage = computed(() => Number(props.attempt.percentage) || 0)

const totalCorrect = computed(() => {
  return props.tests.reduce((sum, test) => {
    return sum + test.answers.filter(a => a.is_correct).length
  }, 0)
})

const totalQuestions = computed(() => {
  return props.tests.reduce((sum, test) => sum + test.answers.length, 0)
})

function capitalizeFirst(str: string | string[] | null | undefined): string {
  if (!str) return ''
  const value = Array.isArray(str) ? str[0] : str
  if (!value) return ''
  return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase()
}

// Violation types and their display info
const violationTypes = {
  'tab_switch': { label: 'Tab Switch', icon: Eye },
  'blur': { label: 'Window Blur', icon: Eye },
  'copy_paste_attempt': { label: 'Copy/Paste Attempt', icon: Copy },
  'right_click_attempt': { label: 'Right-Click Attempt', icon: MousePointer2 },
  'devtools_detected': { label: 'DevTools Detected', icon: Shield },
  'devtools_shortcut_attempt': { label: 'DevTools Shortcut', icon: Shield },
  'screenshot_attempt': { label: 'Screenshot Attempt', icon: Eye },
  'multiple_tab_detected': { label: 'Multiple Tabs', icon: AlertCircle },
  'keyboard_shortcut_blocked': { label: 'Blocked Shortcut', icon: Shield },
}

const violationActivities = computed(() => {
  return activities.value.filter(a =>
    a.activity_type === 'tab_switch' ||
    a.activity_type === 'blur' ||
    a.activity_type === 'copy_paste_attempt' ||
    a.activity_type === 'right_click_attempt' ||
    a.activity_type === 'devtools_detected' ||
    a.activity_type === 'devtools_shortcut_attempt' ||
    a.activity_type === 'screenshot_attempt' ||
    a.activity_type === 'multiple_tab_detected' ||
    a.activity_type === 'keyboard_shortcut_blocked'
  )
})

const totalViolations = computed(() => violationActivities.value.length)

// Violation statistics
const violationsByType = computed(() => {
  const grouped: Record<string, number> = {}
  violationActivities.value.forEach(activity => {
    const type = activity.activity_type
    grouped[type] = (grouped[type] || 0) + 1
  })
  return grouped
})

const criticalViolations = computed(() => {
  return violationActivities.value.filter(a =>
    a.activity_type === 'copy_paste_attempt' ||
    a.activity_type === 'right_click_attempt' ||
    a.activity_type === 'devtools_detected' ||
    a.activity_type === 'devtools_shortcut_attempt' ||
    a.activity_type === 'screenshot_attempt' ||
    a.activity_type === 'multiple_tab_detected'
  ).length
})

const warningViolations = computed(() => {
  return violationActivities.value.filter(a =>
    a.activity_type === 'tab_switch' ||
    a.activity_type === 'blur' ||
    a.activity_type === 'keyboard_shortcut_blocked'
  ).length
})
</script>

<template>
  <Head :title="`Quiz Results: ${props.quiz.title}`" />

  <AuthLayoutStudent>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="route('student.activities.index', props.classlist.id)" as="button">
          <Button variant="outline" size="sm">
            <ArrowLeft class="mr-2 h-4 w-4" /> Back
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-2xl font-bold tracking-tight">Quiz Results</h1>
          <p class="text-sm text-muted-foreground mt-1">{{ props.quiz.title }}</p>
        </div>
      </div>

      <!-- Score Summary Card -->
      <Card class="overflow-hidden border-2">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between">
            <CardTitle class="text-lg flex items-center gap-2">
              <Award class="h-5 w-5" />
              Your Performance
            </CardTitle>
            <Badge variant="outline" class="text-sm px-3 py-1">
              {{ percentage >= 80 ? 'Excellent' : percentage >= 60 ? 'Good' : percentage >= 50 ? 'Pass' : 'Needs Improvement' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="pt-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Main Score -->
            <div class="md:col-span-1 flex flex-col items-center justify-center text-center space-y-2">
              <div class="text-6xl font-bold">
                {{ percentage.toFixed(1) }}%
              </div>
              <div class="text-sm text-muted-foreground">
                {{ props.attempt.score }} / {{ props.attempt.total_points }} points
              </div>
              <div class="flex items-center gap-4 text-xs text-muted-foreground mt-2">
                <div class="flex items-center gap-1">
                  <CheckCircle2 class="h-3 w-3" />
                  <span>{{ totalCorrect }} correct</span>
                </div>
                <div class="flex items-center gap-1">
                  <XCircle class="h-3 w-3" />
                  <span>{{ totalQuestions - totalCorrect }} incorrect</span>
                </div>
              </div>
            </div>

            <!-- Stats Grid -->
            <div class="md:col-span-2 grid grid-cols-2 gap-4">
              <div class="flex items-start gap-3 p-4 border">
                <div class="p-2 border">
                  <TrendingUp class="h-5 w-5" />
                </div>
                <div class="flex-1">
                  <p class="text-xs font-medium text-muted-foreground mb-1">Accuracy</p>
                  <p class="text-2xl font-bold">{{ totalQuestions > 0 ? Math.round((totalCorrect / totalQuestions) * 100) : 0 }}%</p>
                  <p class="text-xs text-muted-foreground mt-1">{{ totalCorrect }} of {{ totalQuestions }} questions</p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-4 border">
                <div class="p-2 border">
                  <Clock class="h-5 w-5" />
                </div>
                <div class="flex-1">
                  <p class="text-xs font-medium text-muted-foreground mb-1">Time Spent</p>
                  <p class="text-2xl font-bold">{{ formatTime(props.attempt.time_spent) }}</p>
                  <p class="text-xs text-muted-foreground mt-1">Completion time</p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-4 border">
                <div class="p-2 border">
                  <Calendar class="h-5 w-5" />
                </div>
                <div class="flex-1">
                  <p class="text-xs font-medium text-muted-foreground mb-1">Submitted</p>
                  <p class="text-sm font-semibold">{{ new Date(props.attempt.submitted_at).toLocaleDateString() }}</p>
                  <p class="text-xs text-muted-foreground mt-1">{{ new Date(props.attempt.submitted_at).toLocaleTimeString() }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3 p-4 border">
                <div class="p-2 border">
                  <FileText class="h-5 w-5" />
                </div>
                <div class="flex-1">
                  <p class="text-xs font-medium text-muted-foreground mb-1">Total Questions</p>
                  <p class="text-2xl font-bold">{{ totalQuestions }}</p>
                  <p class="text-xs text-muted-foreground mt-1">Across {{ props.tests.length }} test{{ props.tests.length !== 1 ? 's' : '' }}</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Violations Section - Always show -->
      <Card class="border-2">
        <CardHeader class="border-b">
          <CardTitle class="flex items-center gap-2">
            <AlertCircle class="h-5 w-5" />
            <span v-if="totalViolations > 0">Violations Detected</span>
            <span v-else>Activity Log</span>
            <Badge v-if="totalViolations > 0" variant="outline" class="ml-2">{{ totalViolations }}</Badge>
            <Badge v-else variant="outline" class="ml-2">No Violations</Badge>
          </CardTitle>
          <CardDescription>
            <span v-if="totalViolations > 0">The following activities were detected and logged during your quiz attempt.</span>
            <span v-else>No violations were detected during your quiz attempt. All activities were normal.</span>
          </CardDescription>
        </CardHeader>
        <CardContent>
          <!-- Violation Statistics -->
          <div v-if="totalViolations > 0" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 border">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Total Violations</p>
                <Badge variant="outline">{{ totalViolations }}</Badge>
              </div>
              <p class="text-2xl font-bold">{{ totalViolations }}</p>
            </div>
            <div class="p-4 border">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Critical</p>
                <Badge variant="outline">{{ criticalViolations }}</Badge>
              </div>
              <p class="text-2xl font-bold">{{ criticalViolations }}</p>
              <p class="text-xs text-muted-foreground mt-1">Copy/paste, DevTools, etc.</p>
            </div>
            <div class="p-4 border">
              <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-muted-foreground">Warnings</p>
                <Badge variant="outline">{{ warningViolations }}</Badge>
              </div>
              <p class="text-2xl font-bold">{{ warningViolations }}</p>
              <p class="text-xs text-muted-foreground mt-1">Tab switches, window blur</p>
            </div>
          </div>

          <!-- No Violations Message -->
          <div v-if="totalViolations === 0" class="text-center py-8">
            <div class="inline-flex items-center justify-center w-16 h-16 border-2 rounded-full mb-4">
              <AlertCircle class="h-8 w-8" />
            </div>
            <h4 class="text-lg font-semibold mb-2">No Violations Detected</h4>
            <p class="text-sm text-muted-foreground">Your quiz attempt was completed without any suspicious activities.</p>
          </div>

          <!-- Violations by Type Breakdown -->
          <div v-if="totalViolations > 0 && Object.keys(violationsByType).length > 0" class="mb-6">
            <h4 class="text-sm font-semibold mb-3">Violations by Type</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <div
                v-for="[type, count] in Object.entries(violationsByType)"
                :key="type"
                class="flex items-center justify-between p-3 border"
              >
                <div class="flex items-center gap-2">
                  <component
                    :is="violationTypes[type as keyof typeof violationTypes]?.icon || AlertCircle"
                    class="h-4 w-4"
                  />
                  <span class="text-sm font-medium">
                    {{ violationTypes[type as keyof typeof violationTypes]?.label || type }}
                  </span>
                </div>
                <Badge variant="outline" class="ml-2">{{ count }}</Badge>
              </div>
            </div>
          </div>

          <Separator v-if="totalViolations > 0" class="mb-6" />

          <!-- Detailed Violations List -->
          <div v-if="totalViolations > 0">
            <h4 class="text-sm font-semibold mb-3">Detailed Log</h4>
            <div class="space-y-3">
              <div
                v-for="activity in violationActivities"
                :key="activity.id"
                class="flex items-start gap-3 p-3 border"
              >
                <div class="p-2 border">
                  <component
                    :is="violationTypes[activity.activity_type as keyof typeof violationTypes]?.icon || AlertCircle"
                    class="h-4 w-4"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-medium">
                      {{ violationTypes[activity.activity_type as keyof typeof violationTypes]?.label || activity.activity_type }}
                    </p>
                    <span class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDate(activity.occurred_at) }}
                    </span>
                  </div>
                  <div v-if="activity.description" class="prose prose-xs dark:prose-invert max-w-none mt-1">
                    <div v-html="activity.description"></div>
                  </div>
                  <div v-if="activity.metadata && Object.keys(activity.metadata).length > 0" class="mt-2 text-xs text-muted-foreground">
                    <span v-if="activity.metadata.count">Count: {{ activity.metadata.count }}</span>
                    <span v-if="activity.metadata.key" class="ml-2">Key: {{ activity.metadata.key }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Results by Test -->
      <div class="space-y-6">
        <div v-for="test in props.tests" :key="test.id">
          <Card class="overflow-hidden border-2">
            <CardHeader class="border-b">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 border">
                      <FileText class="h-5 w-5" />
                    </div>
                    <CardTitle class="text-xl">{{ test.title }}</CardTitle>
                    <Badge v-if="test.type" variant="outline" class="text-xs capitalize">
                      {{ test.type.replace('_', ' ') }}
                    </Badge>
                  </div>
                  <CardDescription v-if="test.description" class="mt-2">
                    <span v-html="test.description"></span>
                  </CardDescription>
                </div>
                <div class="text-right">
                  <p class="text-xs text-muted-foreground">Test Score</p>
                  <p class="text-lg font-bold">
                    {{ test.answers.reduce((sum, a) => sum + a.points_earned, 0) }} /
                    {{ test.answers.reduce((sum, a) => sum + a.question_points, 0) }} pts
                  </p>
                </div>
              </div>
            </CardHeader>
            <CardContent class="p-6">
              <div class="space-y-6">
                <div
                  v-for="(answer, index) in test.answers"
                  :key="answer.question_id"
                  class="relative"
                >
                  <!-- Question Card -->
                  <div class="border-2 p-5">
                    <!-- Question Header -->
                    <div class="flex items-start justify-between mb-4">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                          <span class="inline-flex items-center justify-center w-8 h-8 border-2 font-semibold text-sm">
                            {{ index + 1 }}
                          </span>
                          <Badge variant="outline" class="text-xs font-medium">
                            {{ answer.question_points }} pt{{ answer.question_points !== 1 ? 's' : '' }}
                          </Badge>
                          <Badge variant="outline" class="text-xs font-medium">
                            {{ answer.is_correct ? 'Correct' : 'Incorrect' }}
                          </Badge>
                          <Badge variant="outline" class="text-xs">
                            {{ answer.points_earned }} / {{ answer.question_points }} pts
                          </Badge>
                        </div>
                        <h3 class="text-base font-semibold text-foreground leading-relaxed">
                          {{ answer.question_text }}
                        </h3>
                      </div>
                      <div class="ml-4 flex-shrink-0">
                        <div class="p-2 border-2 rounded-full">
                          <CheckCircle2 v-if="answer.is_correct" class="h-6 w-6" />
                          <XCircle v-else class="h-6 w-6" />
                        </div>
                      </div>
                    </div>

                    <Separator class="my-4" />

                    <!-- Multiple Choice Answers -->
                    <div v-if="answer.question_type === 'multiple_choice' && answer.question_options && answer.question_options.length > 0" class="space-y-3">
                      <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Answer Options</p>
                      <div class="grid gap-2">
                        <div
                          v-for="(option, oIndex) in answer.question_options"
                          :key="oIndex"
                          class="flex items-center gap-3 border-2 p-3"
                          :class="{
                            'border-black dark:border-white':
                              (Array.isArray(answer.correct_answer) && answer.correct_answer.includes(option)) ||
                              (Array.isArray(answer.user_answer) && answer.user_answer.includes(option)),
                            'border':
                              !(Array.isArray(answer.user_answer) && answer.user_answer.includes(option)) &&
                              !(Array.isArray(answer.correct_answer) && answer.correct_answer.includes(option))
                          }"
                        >
                          <div class="flex-shrink-0">
                            <CheckCircle2
                              v-if="Array.isArray(answer.correct_answer) && answer.correct_answer.includes(option)"
                              class="h-5 w-5"
                            />
                            <XCircle
                              v-else-if="Array.isArray(answer.user_answer) && answer.user_answer.includes(option)"
                              class="h-5 w-5"
                            />
                            <div
                              v-else
                              class="h-5 w-5 border-2"
                            />
                          </div>
                          <span
                            class="flex-1 text-sm"
                            :class="{
                              'font-semibold':
                                (Array.isArray(answer.correct_answer) && answer.correct_answer.includes(option)) ||
                                (Array.isArray(answer.user_answer) && answer.user_answer.includes(option))
                            }"
                          >
                            {{ option }}
                          </span>
                          <Badge
                            v-if="Array.isArray(answer.user_answer) && answer.user_answer.includes(option)"
                            variant="outline"
                            class="text-xs"
                          >
                            Your answer
                          </Badge>
                        </div>
                      </div>
                    </div>

                    <!-- True/False Answers -->
                    <div v-if="answer.question_type === 'true_false'" class="space-y-3">
                      <div class="flex items-center gap-4">
                        <div class="flex-1">
                          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Your Answer</p>
                          <Badge variant="outline" class="text-sm px-4 py-2">
                            {{ capitalizeFirst(answer.user_answer) }}
                          </Badge>
                        </div>
                        <div v-if="props.quiz.show_correct_answers" class="flex-1">
                          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Correct Answer</p>
                          <Badge variant="outline" class="text-sm px-4 py-2">
                            {{ capitalizeFirst(answer.correct_answer) }}
                          </Badge>
                        </div>
                      </div>
                    </div>

                    <!-- Short Answer / Essay -->
                    <div v-if="answer.question_type === 'short_answer' || answer.question_type === 'essay'" class="space-y-3">
                      <div>
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Your Answer</p>
                        <div class="border-2 p-4">
                          <p class="text-sm whitespace-pre-wrap">
                            {{ Array.isArray(answer.user_answer) ? answer.user_answer.join(', ') : answer.user_answer || 'No answer provided' }}
                          </p>
                        </div>
                      </div>
                      <div v-if="props.quiz.show_correct_answers">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">Correct Answer</p>
                        <div class="border-2 p-4">
                          <p class="text-sm whitespace-pre-wrap">
                            {{ Array.isArray(answer.correct_answer) ? answer.correct_answer.join(', ') : answer.correct_answer }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Explanation -->
                    <div
                      v-if="answer.explanation && props.quiz.show_correct_answers"
                      class="mt-4 border-2 p-4"
                    >
                      <div class="flex items-start gap-2">
                        <AlertCircle class="h-4 w-4 mt-0.5 flex-shrink-0" />
                        <div class="flex-1">
                          <p class="text-xs font-semibold mb-1 uppercase tracking-wide">Explanation</p>
                          <p class="text-sm leading-relaxed">
                            {{ answer.explanation }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AuthLayoutStudent>
</template>
