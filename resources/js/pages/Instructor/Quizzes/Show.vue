<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Edit, FileText, Clock, Award, Users, BarChart3, CheckCircle2, XCircle } from 'lucide-vue-next'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  quiz: {
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    shuffle_questions: boolean
    show_correct_answers: boolean
    is_published: boolean
    start_date: string | null
    end_date: string | null
    created_at: string
    tests: Array<{
      id: number
      title: string
      type: string | null
      description: string | null
      order: number
      total_points: number
      questions: Array<{
        id: number
        question_text: string
        type: 'multiple_choice' | 'true_false' | 'short_answer' | 'essay'
        points: number
        options: string[]
        correct_answer: string | string[]
        explanation: string | null
        order: number
      }>
    }>
  }
  statistics: {
    total_attempts: number
    unique_users: number
    average_score: number
  }
}>()

function formatDate(value?: string | null) {
  if (!value) return 'Not set'
  return new Date(value).toLocaleString()
}

function getQuestionTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    'multiple_choice': 'Multiple Choice',
    'true_false': 'True/False',
    'short_answer': 'Short Answer',
    'essay': 'Essay'
  }
  return labels[type] || type
}

function getTestTypeLabel(type: string | null): string {
  if (!type) return ''
  const labels: Record<string, string> = {
    'identification': 'Identification',
    'true_false': 'True/False',
    'multiple_choice': 'Multiple Choice',
    'essay': 'Essay',
    'short_answer': 'Short Answer'
  }
  return labels[type] || type
}
</script>

<template>
  <Head :title="`${props.quiz.title} · Quiz`" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('instructor.activities.index', props.classlist.id)" as="button">
            <Button variant="outline" size="sm">
              <ArrowLeft class="h-4 w-4 mr-1" /> Back
            </Button>
          </Link>
          <div>
            <h1 class="text-xl font-semibold">{{ props.quiz.title }}</h1>
            <p class="text-sm text-muted-foreground">
              {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
            </p>
          </div>
        </div>
        <Link :href="route('instructor.quizzes.edit', [props.classlist.id, props.quiz.id])" as="button">
          <Button>
            <Edit class="mr-2 h-4 w-4" /> Edit Quiz
          </Button>
        </Link>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Quiz Information -->
          <Card>
            <CardHeader>
              <CardTitle>Quiz Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="props.quiz.description" class="prose prose-sm dark:prose-invert max-w-none">
                <div v-html="props.quiz.description"></div>
              </div>

              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-muted-foreground">Total Points:</span>
                  <span class="ml-2 font-medium">{{ props.quiz.total_points }}</span>
                </div>
                <div>
                  <span class="text-muted-foreground">Time Limit:</span>
                  <span class="ml-2 font-medium">{{ props.quiz.time_limit ? `${props.quiz.time_limit} minutes` : 'No limit' }}</span>
                </div>
                <div>
                  <span class="text-muted-foreground">Attempts Allowed:</span>
                  <span class="ml-2 font-medium">{{ props.quiz.attempts_allowed }}</span>
                </div>
                <div>
                  <span class="text-muted-foreground">Status:</span>
                  <Badge :variant="props.quiz.is_published ? 'default' : 'secondary'" class="ml-2">
                    {{ props.quiz.is_published ? 'Published' : 'Draft' }}
                  </Badge>
                </div>
                <div>
                  <span class="text-muted-foreground">Start Date:</span>
                  <span class="ml-2 font-medium">{{ formatDate(props.quiz.start_date) }}</span>
                </div>
                <div>
                  <span class="text-muted-foreground">End Date:</span>
                  <span class="ml-2 font-medium">{{ formatDate(props.quiz.end_date) }}</span>
                </div>
              </div>

              <div class="flex flex-wrap gap-2">
                <Badge v-if="props.quiz.shuffle_questions" variant="outline">
                  Shuffle Questions
                </Badge>
                <Badge v-if="props.quiz.show_correct_answers" variant="outline">
                  Show Correct Answers
                </Badge>
              </div>
            </CardContent>
          </Card>

          <!-- Tests -->
          <Card>
            <CardHeader>
              <CardTitle>Tests</CardTitle>
              <CardDescription>{{ props.quiz.tests.length }} test(s) • {{ props.quiz.total_points }} total points</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div v-for="(test, tIndex) in props.quiz.tests" :key="test.id" class="rounded-lg border p-5 space-y-4">
                <div class="flex items-start justify-between">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <FileText class="h-5 w-5 text-primary" />
                      <h3 class="text-lg font-semibold">{{ test.title }}</h3>
                      <Badge v-if="test.type" variant="outline" class="text-xs">
                        {{ getTestTypeLabel(test.type) }}
                      </Badge>
                    </div>
                    <p v-if="test.description" class="text-sm text-muted-foreground">{{ test.description }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ test.questions.length }} question(s) • {{ test.total_points }} points
                    </p>
                  </div>
                </div>

                <Separator />

                <!-- Questions -->
                <div class="space-y-4">
                  <div v-for="(question, qIndex) in test.questions" :key="question.id" class="rounded-lg border p-4 space-y-3">
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                          <span class="text-sm font-medium text-muted-foreground">Question {{ qIndex + 1 }}</span>
                          <Badge variant="outline" class="text-xs">
                            {{ getQuestionTypeLabel(question.type) }}
                          </Badge>
                          <Badge variant="secondary" class="text-xs">
                            {{ question.points }} point{{ question.points !== 1 ? 's' : '' }}
                          </Badge>
                        </div>
                        <p class="text-sm font-medium">{{ question.question_text }}</p>
                      </div>
                    </div>

                    <!-- Multiple Choice Options -->
                    <div v-if="question.type === 'multiple_choice' && question.options && question.options.length > 0" class="space-y-2">
                      <p class="text-xs font-medium text-muted-foreground">Options:</p>
                      <div class="space-y-1">
                        <div
                          v-for="(option, oIndex) in question.options"
                          :key="oIndex"
                          class="flex items-center gap-2 text-sm"
                        >
                          <div
                            :class="[
                              'h-4 w-4 rounded-full border-2 flex items-center justify-center',
                              Array.isArray(question.correct_answer) && question.correct_answer.includes(option)
                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                : 'border-gray-300'
                            ]"
                          >
                            <CheckCircle2
                              v-if="Array.isArray(question.correct_answer) && question.correct_answer.includes(option)"
                              class="h-3 w-3 text-green-500"
                            />
                          </div>
                          <span :class="[
                            'flex-1',
                            Array.isArray(question.correct_answer) && question.correct_answer.includes(option)
                              ? 'font-medium text-green-700 dark:text-green-300'
                              : ''
                          ]">
                            {{ option }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- True/False -->
                    <div v-if="question.type === 'true_false'" class="space-y-2">
                      <p class="text-xs font-medium text-muted-foreground">Correct Answer:</p>
                      <Badge :variant="question.correct_answer === 'true' ? 'default' : 'outline'">
                        {{ question.correct_answer === 'true' ? 'True' : 'False' }}
                      </Badge>
                    </div>

                    <!-- Short Answer / Essay -->
                    <div v-if="question.type === 'short_answer' || question.type === 'essay'" class="space-y-2">
                      <p class="text-xs font-medium text-muted-foreground">Correct Answer:</p>
                      <div class="rounded-md bg-muted p-2 text-sm">
                        {{ Array.isArray(question.correct_answer) ? question.correct_answer.join(', ') : question.correct_answer }}
                      </div>
                    </div>

                    <!-- Explanation -->
                    <div v-if="question.explanation" class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-2 text-xs">
                      <p class="font-medium text-blue-900 dark:text-blue-300 mb-1">Explanation:</p>
                      <p class="text-blue-800 dark:text-blue-200">{{ question.explanation }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar Statistics -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Statistics</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Users class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Total Attempts</span>
                </div>
                <span class="font-semibold">{{ props.statistics.total_attempts }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Users class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Unique Users</span>
                </div>
                <span class="font-semibold">{{ props.statistics.unique_users }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <BarChart3 class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Average Score</span>
                </div>
                <span class="font-semibold">{{ props.statistics.average_score }}/{{ props.quiz.total_points }}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Quiz Details</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Tests:</span>
                <span class="font-medium">{{ props.quiz.tests.length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Total Questions:</span>
                <span class="font-medium">
                  {{ props.quiz.tests.reduce((sum, test) => sum + test.questions.length, 0) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Created:</span>
                <span class="font-medium">{{ formatDate(props.quiz.created_at) }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
