<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Loader2, Save, X, Plus, Trash2, ArrowLeft, GripVertical, ChevronUp, ChevronDown, FileText } from 'lucide-vue-next'
import { ref, computed } from 'vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
}>()

type Question = {
  question_text: string
  type: 'multiple_choice' | 'true_false' | 'short_answer' | 'essay'
  points: number
  options: string[]
  correct_answer: string | string[]
  explanation: string
}

type Test = {
  title: string
  type: string
  description: string
  questions: Question[]
}

const form = useForm({
  title: '',
  description: '',
  time_limit: null as number | null,
  attempts_allowed: 1,
  shuffle_questions: false,
  show_correct_answers: false,
  is_published: false,
  require_proctoring: false,
  start_date: '' as string | null,
  end_date: '' as string | null,
  tests: [] as Test[],
})

const tests = ref<Test[]>([
  {
    title: 'Test I - Identification',
    type: 'identification',
    description: '',
    questions: [{
      question_text: '',
      type: 'short_answer',
      points: 1,
      options: [],
      correct_answer: '',
      explanation: '',
    }]
  }
])

const processing = computed(() => form.processing)

function addTest() {
  const testNumber = tests.value.length + 1
  const testTypes = ['identification', 'true_false', 'multiple_choice', 'essay', 'short_answer']
  const defaultType = testTypes[(testNumber - 1) % testTypes.length]
  
  tests.value.push({
    title: `Test ${getRomanNumeral(testNumber)} - ${getTestTypeName(defaultType)}`,
    type: defaultType,
    description: '',
    questions: [{
      question_text: '',
      type: defaultType === 'identification' ? 'short_answer' : defaultType === 'true_false' ? 'true_false' : 'multiple_choice',
      points: 1,
      options: defaultType === 'multiple_choice' ? ['', '', '', ''] : [],
      correct_answer: '',
      explanation: '',
    }]
  })
}

function getRomanNumeral(num: number): string {
  const values = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1]
  const numerals = ['M', 'CM', 'D', 'CD', 'C', 'XC', 'L', 'XL', 'X', 'IX', 'V', 'IV', 'I']
  let result = ''
  for (let i = 0; i < values.length; i++) {
    while (num >= values[i]) {
      result += numerals[i]
      num -= values[i]
    }
  }
  return result
}

function getTestTypeName(type: string): string {
  const names: Record<string, string> = {
    'identification': 'Identification',
    'true_false': 'True/False',
    'multiple_choice': 'Multiple Choice',
    'essay': 'Essay',
    'short_answer': 'Short Answer'
  }
  return names[type] || 'Test'
}

function removeTest(index: number) {
  tests.value.splice(index, 1)
}

function moveTestUp(index: number) {
  if (index > 0) {
    const temp = tests.value[index]
    tests.value[index] = tests.value[index - 1]
    tests.value[index - 1] = temp
  }
}

function moveTestDown(index: number) {
  if (index < tests.value.length - 1) {
    const temp = tests.value[index]
    tests.value[index] = tests.value[index + 1]
    tests.value[index + 1] = temp
  }
}

function getQuestionTypeFromTestType(testType: string): 'multiple_choice' | 'true_false' | 'short_answer' | 'essay' {
  if (testType === 'identification' || testType === 'short_answer') {
    return 'short_answer'
  } else if (testType === 'true_false') {
    return 'true_false'
  } else if (testType === 'multiple_choice') {
    return 'multiple_choice'
  } else if (testType === 'essay') {
    return 'essay'
  }
  return 'short_answer' // default
}

function addQuestion(testIndex: number) {
  const test = tests.value[testIndex]
  const questionType = getQuestionTypeFromTestType(test.type)
  test.questions.push({
    question_text: '',
    type: questionType,
    points: 1,
    options: questionType === 'multiple_choice' ? ['', '', '', ''] : [],
    correct_answer: '',
    explanation: '',
  })
}

function removeQuestion(testIndex: number, questionIndex: number) {
  tests.value[testIndex].questions.splice(questionIndex, 1)
}

function moveQuestionUp(testIndex: number, questionIndex: number) {
  const questions = tests.value[testIndex].questions
  if (questionIndex > 0) {
    const temp = questions[questionIndex]
    questions[questionIndex] = questions[questionIndex - 1]
    questions[questionIndex - 1] = temp
  }
}

function moveQuestionDown(testIndex: number, questionIndex: number) {
  const questions = tests.value[testIndex].questions
  if (questionIndex < questions.length - 1) {
    const temp = questions[questionIndex]
    questions[questionIndex] = questions[questionIndex + 1]
    questions[questionIndex + 1] = temp
  }
}

function updateQuestionOption(testIndex: number, questionIndex: number, optionIndex: number, value: string) {
  const question = tests.value[testIndex].questions[questionIndex]
  const oldValue = question.options[optionIndex]
  question.options[optionIndex] = value
  
  if (question.correct_answer === oldValue) {
    question.correct_answer = value
  }
}

function addOption(testIndex: number, questionIndex: number) {
  tests.value[testIndex].questions[questionIndex].options.push('')
}

function removeOption(testIndex: number, questionIndex: number, optionIndex: number) {
  const question = tests.value[testIndex].questions[questionIndex]
  const option = question.options[optionIndex]
  if (question.correct_answer === option) {
    question.correct_answer = ''
  }
  question.options.splice(optionIndex, 1)
}

function submit() {
  // Validate tests
  if (tests.value.length === 0) {
    toast.error('Please add at least one test.')
    return
  }

  // Validate each test and its questions
  for (let tIndex = 0; tIndex < tests.value.length; tIndex++) {
    const test = tests.value[tIndex]
    if (!test.title.trim()) {
      toast.error(`Test ${tIndex + 1}: Please enter a test title.`)
      return
    }
    if (test.questions.length === 0) {
      toast.error(`${test.title}: Please add at least one question.`)
      return
    }

    for (let qIndex = 0; qIndex < test.questions.length; qIndex++) {
      const q = test.questions[qIndex]
      if (!q.question_text.trim()) {
        toast.error(`${test.title} - Question ${qIndex + 1}: Please enter question text.`)
        return
      }
      if (q.points < 1) {
        toast.error(`${test.title} - Question ${qIndex + 1}: Points must be at least 1.`)
        return
      }
      if (q.type === 'multiple_choice') {
        const validOptions = q.options.filter(opt => opt.trim())
        if (validOptions.length < 2) {
          toast.error(`${test.title} - Question ${qIndex + 1}: Multiple choice needs at least 2 options.`)
          return
        }
        if (!q.correct_answer || (typeof q.correct_answer === 'string' && q.correct_answer.trim() === '')) {
          toast.error(`${test.title} - Question ${qIndex + 1}: Please select a correct answer.`)
          return
        }
      }
      if (q.type === 'true_false' && !q.correct_answer) {
        toast.error(`${test.title} - Question ${qIndex + 1}: Please select correct answer (True/False).`)
        return
      }
      if ((q.type === 'short_answer' || q.type === 'essay') && !q.correct_answer) {
        toast.error(`${test.title} - Question ${qIndex + 1}: Please provide a correct answer.`)
        return
      }
    }
  }

  // Prepare form data
  form.tests = tests.value.map(test => ({
    title: test.title,
    type: test.type || null,
    description: test.description || null,
    questions: test.questions.map(q => {
      const filteredOptions = q.type === 'multiple_choice' ? q.options.filter(opt => opt.trim()) : []
      
      let correctAnswer = q.correct_answer
      if (q.type === 'multiple_choice' && typeof correctAnswer === 'string') {
        if (correctAnswer.startsWith('__index_')) {
          correctAnswer = ''
        }
        if (correctAnswer && !filteredOptions.includes(correctAnswer)) {
          correctAnswer = filteredOptions[0] || ''
        }
      }
      
      return {
        question_text: q.question_text,
        type: q.type,
        points: q.points,
        options: q.type === 'multiple_choice' ? filteredOptions : null,
        correct_answer: correctAnswer,
        explanation: q.explanation || null,
      }
    })
  }))

  form.post(route('instructor.examinations.store', props.classlist.id), {
    onSuccess: () => {
      toast.success('Examination created successfully.')
      router.visit(route('instructor.activities.index', props.classlist.id))
    },
    onError: () => toast.error('Unable to create examination.'),
    onFinish: () => form.clearErrors(),
  })
}

function saveAsDraft() {
  if (tests.value.length === 0) {
    toast.error('Please add at least one test.')
    return
  }

  for (let tIndex = 0; tIndex < tests.value.length; tIndex++) {
    const test = tests.value[tIndex]
    if (!test.title.trim()) {
      toast.error(`Test ${tIndex + 1}: Please enter a test title.`)
      return
    }
  }

  form.tests = tests.value.map(test => ({
    title: test.title,
    type: test.type || null,
    description: test.description || null,
    questions: test.questions.map(q => {
      const filteredOptions = q.type === 'multiple_choice' ? q.options.filter(opt => opt.trim()) : []
      
      let correctAnswer = q.correct_answer
      if (q.type === 'multiple_choice' && typeof correctAnswer === 'string') {
        if (correctAnswer.startsWith('__index_')) {
          correctAnswer = ''
        }
        if (correctAnswer && !filteredOptions.includes(correctAnswer)) {
          correctAnswer = filteredOptions[0] || ''
        }
      }
      
      return {
        question_text: q.question_text || '',
        type: q.type,
        points: q.points || 1,
        options: q.type === 'multiple_choice' ? filteredOptions : null,
        correct_answer: correctAnswer || '',
        explanation: q.explanation || null,
      }
    })
  }))

  form.is_published = false
  form.post(route('instructor.examinations.store', props.classlist.id), {
    onSuccess: () => {
      toast.success('Examination saved as draft.')
      router.visit(route('instructor.activities.index', props.classlist.id))
    },
    onError: () => toast.error('Unable to save draft.'),
    onFinish: () => form.clearErrors(),
  })
}

const totalPoints = computed(() => {
  return tests.value.reduce((sum, test) => {
    return sum + test.questions.reduce((testSum, q) => testSum + (q.points || 0), 0)
  }, 0)
})

const totalQuestions = computed(() => {
  return tests.value.reduce((sum, test) => sum + test.questions.length, 0)
})
</script>

<template>
  <Head title="Create Examination" />

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
            <h1 class="text-xl font-semibold">Create Examination</h1>
            <p class="text-sm text-muted-foreground">
              {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="saveAsDraft" :disabled="processing">
            <Save class="mr-2 h-4 w-4" />
            Save as Draft
          </Button>
          <Button @click="submit" :disabled="processing">
            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
            <Save v-else class="mr-2 h-4 w-4" />
            {{ processing ? 'Creating...' : 'Create Examination' }}
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>Set up your examination details</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <Label for="title">Examination Title <span class="text-destructive">*</span></Label>
                <Input id="title" v-model="form.title" placeholder="e.g., Midterm Examination" required />
                <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                  <AlertDescription>{{ form.errors.title }}</AlertDescription>
                </Alert>
              </div>

              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="form.description" rows="3" placeholder="Examination instructions..." />
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="time_limit">Time Limit (minutes)</Label>
                  <Input id="time_limit" type="number" min="1" v-model.number="form.time_limit" placeholder="Optional" />
                </div>
                <div class="space-y-2">
                  <Label for="attempts_allowed">Attempts Allowed <span class="text-destructive">*</span></Label>
                  <Input id="attempts_allowed" type="number" min="1" max="10" v-model.number="form.attempts_allowed" required />
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="start_date">Start Date & Time</Label>
                  <Input id="start_date" type="datetime-local" v-model="form.start_date" />
                </div>
                <div class="space-y-2">
                  <Label for="end_date">End Date & Time</Label>
                  <Input id="end_date" type="datetime-local" v-model="form.end_date" />
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Tests Section -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Tests</CardTitle>
                  <CardDescription>{{ tests.length }} test(s) • {{ totalQuestions }} question(s) • {{ totalPoints }} total points</CardDescription>
                </div>
                <Button type="button" variant="outline" size="sm" @click="addTest">
                  <Plus class="mr-2 h-4 w-4" /> Add Test
                </Button>
              </div>
            </CardHeader>
            <CardContent class="space-y-6">
              <div v-for="(test, tIndex) in tests" :key="tIndex" class="rounded-lg border-2 p-5 space-y-4 bg-card">
                <!-- Test Header -->
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3 flex-1">
                    <div class="flex flex-col gap-1">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="tIndex === 0"
                        @click="moveTestUp(tIndex)"
                      >
                        <ChevronUp class="h-4 w-4" />
                      </Button>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="tIndex === tests.length - 1"
                        @click="moveTestDown(tIndex)"
                      >
                        <ChevronDown class="h-4 w-4" />
                      </Button>
                    </div>
                    <div class="flex-1 space-y-2">
                      <div class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-primary" />
                        <Input
                          v-model="test.title"
                          placeholder="e.g., Test I - Identification"
                          class="font-semibold text-lg"
                        />
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                          <Label class="text-xs text-muted-foreground">Test Type</Label>
                          <Select 
                            v-model="test.type"
                            @update:model-value="(val) => {
                              test.type = val
                              const questionType = getQuestionTypeFromTestType(val)
                              // Update all questions in this test to match the test type
                              test.questions.forEach(q => {
                                q.type = questionType
                                q.correct_answer = ''
                                if (questionType === 'multiple_choice') {
                                  if (q.options.length === 0) {
                                    q.options = ['', '', '', '']
                                  }
                                } else {
                                  q.options = []
                                }
                              })
                            }"
                          >
                            <SelectTrigger class="text-sm">
                              <SelectValue placeholder="Select test type" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="identification">Identification</SelectItem>
                              <SelectItem value="true_false">True/False</SelectItem>
                              <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                              <SelectItem value="short_answer">Short Answer</SelectItem>
                              <SelectItem value="essay">Essay</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                        <div class="space-y-1">
                          <Label class="text-xs text-muted-foreground">Description (optional)</Label>
                          <Textarea
                            v-model="test.description"
                            placeholder="Test description"
                            rows="1"
                            class="text-sm"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <Button
                    v-if="tests.length > 1"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeTest(tIndex)"
                  >
                    <Trash2 class="h-4 w-4 text-destructive" />
                  </Button>
                </div>

                <Separator />

                <!-- Questions in this Test -->
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <Label class="text-base font-semibold">{{ test.questions.length }} Question(s)</Label>
                    <Button type="button" variant="outline" size="sm" @click="addQuestion(tIndex)">
                      <Plus class="mr-2 h-4 w-4" /> Add Question
                    </Button>
                  </div>

                  <div v-for="(question, qIndex) in test.questions" :key="qIndex" class="rounded-lg border p-4 space-y-4 bg-muted/30">
                    <div class="flex items-start justify-between">
                      <div class="flex items-center gap-3">
                        <div class="flex flex-col gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-6 w-6"
                            :disabled="qIndex === 0"
                            @click="moveQuestionUp(tIndex, qIndex)"
                          >
                            <ChevronUp class="h-4 w-4" />
                          </Button>
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-6 w-6"
                            :disabled="qIndex === test.questions.length - 1"
                            @click="moveQuestionDown(tIndex, qIndex)"
                          >
                            <ChevronDown class="h-4 w-4" />
                          </Button>
                        </div>
                        <div class="flex items-center gap-2">
                          <GripVertical class="h-5 w-5 text-muted-foreground" />
                          <span class="font-medium">Question {{ qIndex + 1 }}</span>
                        </div>
                      </div>
                      <Button
                        v-if="test.questions.length > 1"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeQuestion(tIndex, qIndex)"
                      >
                        <Trash2 class="h-4 w-4 text-destructive" />
                      </Button>
                    </div>

                    <div class="space-y-2">
                      <Label>Question Text <span class="text-destructive">*</span></Label>
                      <Textarea v-model="question.question_text" rows="2" placeholder="Enter your question..." required />
                    </div>

                    <div class="space-y-2">
                      <Label>Points <span class="text-destructive">*</span></Label>
                      <Input type="number" min="1" v-model.number="question.points" class="w-32" />
                    </div>

                    <!-- Multiple Choice Options -->
                    <div v-if="question.type === 'multiple_choice'" class="space-y-3">
                      <Label>Options <span class="text-destructive">*</span> (Select the correct answer)</Label>
                      <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center gap-3">
                        <RadioGroup 
                          :model-value="question.correct_answer"
                          @update:model-value="question.correct_answer = $event"
                          class="flex items-center"
                        >
                          <RadioGroupItem 
                            :value="option.trim() || `__index_${oIndex}__`" 
                            :id="`t${tIndex}-q${qIndex}-opt${oIndex}`"
                            :disabled="!option.trim()"
                          />
                        </RadioGroup>
                        <Input
                          :id="`t${tIndex}-q${qIndex}-opt${oIndex}`"
                          :model-value="option"
                          :placeholder="`Option ${oIndex + 1}`"
                          @input="(e) => updateQuestionOption(tIndex, qIndex, oIndex, (e.target as HTMLInputElement).value)"
                          class="flex-1"
                        />
                        <Button
                          v-if="question.options.length > 2"
                          type="button"
                          variant="ghost"
                          size="icon"
                          @click="removeOption(tIndex, qIndex, oIndex)"
                        >
                          <X class="h-4 w-4" />
                        </Button>
                      </div>
                      <Button type="button" variant="outline" size="sm" @click="addOption(tIndex, qIndex)">
                        <Plus class="mr-2 h-4 w-4" /> Add Option
                      </Button>
                    </div>

                    <!-- True/False -->
                    <div v-if="question.type === 'true_false'" class="space-y-2">
                      <Label>Correct Answer <span class="text-destructive">*</span></Label>
                      <RadioGroup v-model="question.correct_answer">
                        <div class="flex items-center space-x-2">
                          <RadioGroupItem value="true" :id="`t${tIndex}-q${qIndex}-true`" />
                          <Label :for="`t${tIndex}-q${qIndex}-true`" class="cursor-pointer">True</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                          <RadioGroupItem value="false" :id="`t${tIndex}-q${qIndex}-false`" />
                          <Label :for="`t${tIndex}-q${qIndex}-false`" class="cursor-pointer">False</Label>
                        </div>
                      </RadioGroup>
                    </div>

                    <!-- Short Answer / Essay -->
                    <div v-if="question.type === 'short_answer' || question.type === 'essay'" class="space-y-2">
                      <Label>Correct Answer <span class="text-muted-foreground text-xs">(for auto-grading, comma-separated for multiple acceptable answers)</span></Label>
                      <Textarea
                        v-model="question.correct_answer"
                        :rows="question.type === 'essay' ? 4 : 2"
                        placeholder="Enter correct answer(s)..."
                      />
                    </div>

                    <div class="space-y-2">
                      <Label>Explanation (optional)</Label>
                      <Textarea v-model="question.explanation" rows="2" placeholder="Explanation shown after submission..." />
                    </div>
                  </div>

                  <Alert v-if="test.questions.length === 0" variant="default">
                    <AlertDescription>Add at least one question to this test.</AlertDescription>
                  </Alert>
                </div>
              </div>

              <Alert v-if="tests.length === 0" variant="default">
                <AlertDescription>Add at least one test to create the examination.</AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar Settings -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Settings</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="space-y-0.5">
                  <div class="font-medium">Shuffle Questions</div>
                  <p class="text-xs text-muted-foreground">Randomize order</p>
                </div>
                <Switch v-model="form.shuffle_questions" />
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="space-y-0.5">
                  <div class="font-medium">Show Correct Answers</div>
                  <p class="text-xs text-muted-foreground">After submission</p>
                </div>
                <Switch v-model="form.show_correct_answers" />
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="space-y-0.5">
                  <div class="font-medium">Require Proctoring</div>
                  <p class="text-xs text-muted-foreground">Supervision required</p>
                </div>
                <Switch v-model="form.require_proctoring" />
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="space-y-0.5">
                  <div class="font-medium">Publish Examination</div>
                  <p class="text-xs text-muted-foreground">Make available</p>
                </div>
                <Switch v-model="form.is_published" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Summary</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Tests:</span>
                <span class="font-medium">{{ tests.length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Questions:</span>
                <span class="font-medium">{{ totalQuestions }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Total Points:</span>
                <span class="font-medium">{{ totalPoints }}</span>
              </div>
              <div v-if="form.time_limit" class="flex justify-between">
                <span class="text-muted-foreground">Time Limit:</span>
                <span class="font-medium">{{ form.time_limit }} min</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Attempts:</span>
                <span class="font-medium">{{ form.attempts_allowed }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
