<script setup lang="ts">
import QuizExamLayout from '@/layouts/QuizExamLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Clock, Save, Send, FileText } from 'lucide-vue-next'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { toast } from 'vue-sonner'
import { useAntiCheating } from '@/composables/useAntiCheating'

const props = defineProps<{
  classlist: { id: string; name: string }
  examination: { id: number; title: string; time_limit: number | null; total_points: number }
  attempt: { id: number; started_at: string }
  tests: Array<{
    id: number
    title: string
    type: string | null
    description: string | null
    order: number
    questions: Array<{
      id: number
      question_text: string
      type: string
      points: number
      options: string[]
      order: number
    }>
  }>
  questions: Array<{
    id: number
    test_id: number
    test_title: string
    question_text: string
    type: string
    points: number
    options: string[]
    order: number
  }>
  answers: Record<number, { answer: string[] }>
}>()

const currentQuestionIndex = ref(0)
const answers = ref<Record<number, string[]>>({})
const timeRemaining = ref<number | null>(null)
const isSubmitting = ref(false)
let timerInterval: ReturnType<typeof setInterval> | null = null

// Initialize anti-cheating
const antiCheating = useAntiCheating(props.attempt.id, 'ExamAttempt')

// Block if multiple tabs detected
watch(() => antiCheating.isMultipleTabOpen.value, (isOpen) => {
  if (isOpen) {
    toast.error('Multiple tabs detected! Please close other tabs immediately.', { duration: 10000 })
    // Optionally auto-submit or block further actions
  }
})

// Initialize answers from props
onMounted(() => {
  // Start timer for first question
  if (props.questions.length > 0) {
    antiCheating.startQuestionTimer(props.questions[0].id)
  }
  Object.keys(props.answers).forEach((qId) => {
    answers.value[Number(qId)] = props.answers[Number(qId)].answer
  })
  
  // Initialize timer if time limit exists
  if (props.examination.time_limit) {
    const startedAt = new Date(props.attempt.started_at).getTime()
    const timeLimitMs = props.examination.time_limit * 60 * 1000
    const elapsed = Date.now() - startedAt
    timeRemaining.value = Math.max(0, timeLimitMs - elapsed)
    
    timerInterval = setInterval(() => {
      if (timeRemaining.value !== null) {
        timeRemaining.value -= 1000
        if (timeRemaining.value <= 0) {
          if (timerInterval) clearInterval(timerInterval)
          autoSubmit()
        }
      }
    }, 1000)
  }
})

onBeforeUnmount(() => {
  if (timerInterval) clearInterval(timerInterval)
})

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value])
const currentTest = computed(() => {
  if (!currentQuestion.value) return null
  return props.tests.find(t => t.id === currentQuestion.value.test_id)
})
const progress = computed(() => ((currentQuestionIndex.value + 1) / props.questions.length) * 100)
const answeredCount = computed(() => Object.keys(answers.value).filter(k => answers.value[Number(k)] && answers.value[Number(k)].length > 0).length)

function formatTime(ms: number): string {
  const totalSeconds = Math.floor(ms / 1000)
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

function saveAnswer(questionId: number, answer: string | string[]) {
  const answerArray = Array.isArray(answer) ? answer : [answer]
  const oldAnswer = answers.value[questionId]
  
  // Track answer change
  if (oldAnswer && JSON.stringify(oldAnswer) !== JSON.stringify(answerArray)) {
    antiCheating.trackAnswerChange(questionId, oldAnswer, answerArray)
  }
  
  answers.value[questionId] = answerArray
  
  router.post(
    route('student.examinations.saveAnswer', [props.classlist.id, props.examination.id, props.attempt.id]),
    { question_id: questionId, answer: answerArray },
    { preserveState: true, preserveScroll: true, only: [] }
  )
}

function goToQuestion(index: number) {
  if (index >= 0 && index < props.questions.length) {
    // Stop timer for current question
    if (currentQuestion.value) {
      antiCheating.stopQuestionTimer(currentQuestion.value.id)
    }
    
    currentQuestionIndex.value = index
    
    // Start timer for new question
    if (currentQuestion.value) {
      antiCheating.startQuestionTimer(currentQuestion.value.id)
    }
  }
}

function submitExamination() {
  if (isSubmitting.value) return
  
  if (answeredCount.value < props.questions.length) {
    if (!confirm(`You have ${props.questions.length - answeredCount.value} unanswered questions. Submit anyway?`)) {
      return
    }
  }
  
  isSubmitting.value = true
  if (timerInterval) clearInterval(timerInterval)
  
  router.post(
    route('student.examinations.submit', [props.classlist.id, props.examination.id, props.attempt.id]),
    {},
    {
      onSuccess: () => {
        toast.success('Examination submitted successfully!')
      },
      onError: (errors) => {
        isSubmitting.value = false
        const errorMessage = errors?.error || errors?.message || 'Failed to submit examination'
        toast.error(errorMessage)
        console.error('Examination submission error:', errors)
      }
    }
  )
}

function autoSubmit() {
  toast.warning('Time is up! Submitting examination...')
  submitExamination()
}

// Get question number within current test
const questionNumberInTest = computed(() => {
  if (!currentTest.value || !currentQuestion.value) return 0
  const testQuestions = currentTest.value.questions
  const indexInTest = testQuestions.findIndex(q => q.id === currentQuestion.value.id)
  return indexInTest + 1
})

// Get total questions in current test
const totalQuestionsInTest = computed(() => {
  return currentTest.value?.questions.length || 0
})

// Watch for question changes to track time
watch(() => currentQuestion.value?.id, (newQuestionId, oldQuestionId) => {
  if (oldQuestionId && newQuestionId && oldQuestionId !== newQuestionId) {
    antiCheating.stopQuestionTimer(oldQuestionId)
    antiCheating.startQuestionTimer(newQuestionId)
  }
})
</script>

<template>
  <QuizExamLayout :title="`Taking Examination: ${props.examination.title}`">
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 flex flex-col gap-4 p-6 w-full">
      <!-- Anti-Cheating Warning Banner -->
      <Alert v-if="antiCheating.violationCount.value > 0 || antiCheating.isMultipleTabOpen.value" variant="destructive" class="mb-4">
        <AlertDescription>
          <div class="flex items-center justify-between">
            <span>
              <strong>Warning:</strong> 
              <span v-if="antiCheating.isMultipleTabOpen.value">Multiple tabs detected. </span>
              <span v-if="antiCheating.violationCount.value > 0">
                {{ antiCheating.violationCount.value }} violation(s) detected. All activities are being logged.
              </span>
            </span>
          </div>
        </AlertDescription>
      </Alert>

      <!-- Header -->
      <div class="flex items-center justify-between rounded-lg border bg-card p-4">
        <div>
          <h1 class="text-lg font-semibold">{{ props.examination.title }}</h1>
          <p class="text-sm text-muted-foreground">{{ props.classlist.name }}</p>
          <div v-if="currentTest" class="mt-1 flex items-center gap-2">
            <FileText class="h-4 w-4 text-primary" />
            <span class="text-xs font-medium text-muted-foreground">{{ currentTest.title }}</span>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div v-if="timeRemaining !== null" class="flex items-center gap-2 rounded-lg bg-muted px-3 py-2">
            <Clock class="h-4 w-4" />
            <span class="font-mono font-semibold" :class="{ 'text-destructive': timeRemaining < 300000 }">
              {{ formatTime(timeRemaining) }}
            </span>
          </div>
          <div class="text-sm">
            <span class="font-medium">{{ answeredCount }}/{{ props.questions.length }}</span>
            <span class="text-muted-foreground"> answered</span>
          </div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
        <div
          class="h-full bg-primary transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>

      <!-- Question Navigation -->
      <div v-if="props.questions.length > 0" class="space-y-2">
        <div class="text-xs font-medium text-muted-foreground">Question Navigation</div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(q, index) in props.questions"
            :key="q.id"
            @click="goToQuestion(index)"
            class="h-8 w-8 rounded border transition-colors text-xs"
            :class="{
              'bg-primary text-primary-foreground': index === currentQuestionIndex,
              'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-300': index !== currentQuestionIndex && answers[q.id] && answers[q.id].length > 0,
              'bg-muted': index !== currentQuestionIndex && (!answers[q.id] || answers[q.id].length === 0)
            }"
            :title="`${q.test_title} - Question ${index + 1}`"
          >
            {{ index + 1 }}
          </button>
        </div>
      </div>

      <!-- No Questions Message -->
      <Card v-if="props.questions.length === 0" class="flex-1">
        <CardContent class="flex flex-col items-center justify-center py-12">
          <FileText class="h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-lg font-semibold mb-2">No Questions Available</h3>
          <p class="text-sm text-muted-foreground text-center">
            This examination does not have any questions yet. Please contact your instructor.
          </p>
        </CardContent>
      </Card>

      <!-- Current Question -->
      <Card v-else-if="currentQuestion" class="flex-1">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Badge variant="outline" class="text-xs">
                {{ currentTest?.title || 'Question' }}
              </Badge>
              <span class="text-sm font-medium text-muted-foreground">
                Question {{ questionNumberInTest }} of {{ totalQuestionsInTest }} in this test
              </span>
            </div>
            <span class="text-sm font-medium">{{ currentQuestion.points }} points</span>
          </div>
        </CardHeader>
        <CardContent class="space-y-6">
          <h2 class="text-lg font-semibold">{{ currentQuestion.question_text }}</h2>

          <!-- Multiple Choice -->
          <div v-if="currentQuestion.type === 'multiple_choice' && currentQuestion.options && currentQuestion.options.length > 0">
            <RadioGroup
              :model-value="answers[currentQuestion.id]?.[0] || ''"
              @update:model-value="saveAnswer(currentQuestion.id, $event)"
            >
              <div
                v-for="(option, index) in currentQuestion.options"
                :key="index"
                class="mb-3 flex items-center space-x-2 rounded-lg border p-3 hover:bg-accent cursor-pointer"
              >
                <RadioGroupItem :value="option.trim()" :id="`option-${index}`" />
                <Label :for="`option-${index}`" class="flex-1 cursor-pointer">
                  {{ option }}
                </Label>
              </div>
            </RadioGroup>
          </div>

          <!-- True/False -->
          <div v-else-if="currentQuestion.type === 'true_false'">
            <RadioGroup
              :model-value="answers[currentQuestion.id]?.[0] || ''"
              @update:model-value="saveAnswer(currentQuestion.id, $event)"
            >
              <div class="mb-3 flex items-center space-x-2 rounded-lg border p-3 hover:bg-accent cursor-pointer">
                <RadioGroupItem value="true" id="true" />
                <Label for="true" class="flex-1 cursor-pointer">True</Label>
              </div>
              <div class="mb-3 flex items-center space-x-2 rounded-lg border p-3 hover:bg-accent cursor-pointer">
                <RadioGroupItem value="false" id="false" />
                <Label for="false" class="flex-1 cursor-pointer">False</Label>
              </div>
            </RadioGroup>
          </div>

          <!-- Short Answer / Essay -->
          <div v-else>
            <Textarea
              :model-value="answers[currentQuestion.id]?.[0] || ''"
              @update:model-value="saveAnswer(currentQuestion.id, $event)"
              :placeholder="currentQuestion.type === 'essay' ? 'Type your essay answer here...' : 'Type your answer here...'"
              :class="currentQuestion.type === 'essay' ? 'min-h-[200px]' : 'min-h-[100px]'"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Navigation Buttons -->
      <div class="flex items-center justify-between">
        <Button
          variant="outline"
          :disabled="currentQuestionIndex === 0"
          @click="goToQuestion(currentQuestionIndex - 1)"
        >
          Previous
        </Button>
        <div class="flex gap-2">
          <Button
            variant="outline"
            @click="saveAnswer(currentQuestion.id, answers[currentQuestion.id] || [])"
          >
            <Save class="mr-2 h-4 w-4" /> Save
          </Button>
          <Button
            v-if="currentQuestionIndex < props.questions.length - 1"
            @click="goToQuestion(currentQuestionIndex + 1)"
          >
            Next
          </Button>
          <Button
            v-else
            @click="submitExamination"
            :disabled="isSubmitting"
            class="bg-emerald-600 hover:bg-emerald-700"
          >
            <Send class="mr-2 h-4 w-4" /> {{ isSubmitting ? 'Submitting...' : 'Submit Examination' }}
          </Button>
        </div>
      </div>
      </div>
    </div>
  </QuizExamLayout>
</template>
