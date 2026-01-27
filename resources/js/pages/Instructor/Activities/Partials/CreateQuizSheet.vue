<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
import { Switch } from '@/components/ui/switch'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter, SheetClose
} from '@/components/ui/sheet'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, Save, X, Plus, Trash2, GripVertical } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  classlist: { id: string; name: string; room: string; academic_year: string }
  other_classlists: Array<{ id: number; name: string; room: string; section: string | null; academic_year: string | null }>
}>()

const emit = defineEmits<{ (e:'update:open', v:boolean):void }>()

type Question = {
  question_text: string
  type: 'multiple_choice' | 'true_false' | 'short_answer' | 'essay'
  points: number
  options: string[]
  correct_answer: string | string[]
  explanation: string
}

const form = useForm({
  title: '',
  description: '',
  time_limit: null as number | null,
  attempts_allowed: 1,
  shuffle_questions: false,
  show_correct_answers: true,
  is_published: false,
  start_date: '' as string | null,
  end_date: '' as string | null,
  questions: [] as Question[],
  also_classlist_ids: [] as number[],
})

const questions = ref<Question[]>([
  {
    question_text: '',
    type: 'multiple_choice',
    points: 1,
    options: ['', '', '', ''],
    correct_answer: '',
    explanation: '',
  }
])

const processing = computed(() => form.processing)

function addQuestion() {
  questions.value.push({
    question_text: '',
    type: 'multiple_choice',
    points: 1,
    options: ['', '', '', ''],
    correct_answer: '',
    explanation: '',
  })
}

function removeQuestion(index: number) {
  questions.value.splice(index, 1)
}

function updateQuestionOption(questionIndex: number, optionIndex: number, value: string) {
  questions.value[questionIndex].options[optionIndex] = value
}

function addOption(questionIndex: number) {
  questions.value[questionIndex].options.push('')
}

function removeOption(questionIndex: number, optionIndex: number) {
  questions.value[questionIndex].options.splice(optionIndex, 1)
}

function submit() {
  // Validate questions
  if (questions.value.length === 0) {
    toast.error('Please add at least one question.')
    return
  }

  // Validate each question
  for (let i = 0; i < questions.value.length; i++) {
    const q = questions.value[i]
    if (!q.question_text.trim()) {
      toast.error(`Question ${i + 1}: Please enter question text.`)
      return
    }
    if (q.points < 1) {
      toast.error(`Question ${i + 1}: Points must be at least 1.`)
      return
    }
    if (q.type === 'multiple_choice') {
      const validOptions = q.options.filter(opt => opt.trim())
      if (validOptions.length < 2) {
        toast.error(`Question ${i + 1}: Multiple choice needs at least 2 options.`)
        return
      }
      if (!q.correct_answer || (typeof q.correct_answer === 'string' && q.correct_answer === '')) {
        toast.error(`Question ${i + 1}: Please select a correct answer.`)
        return
      }
      // Validate that the selected answer index is valid
      const answerIndex = parseInt(q.correct_answer as string)
      if (isNaN(answerIndex) || !validOptions[answerIndex]) {
        toast.error(`Question ${i + 1}: Please select a valid correct answer.`)
        return
      }
    }
    if (q.type === 'true_false' && !q.correct_answer) {
      toast.error(`Question ${i + 1}: Please select correct answer (True/False).`)
      return
    }
    if ((q.type === 'short_answer' || q.type === 'essay') && !q.correct_answer) {
      toast.error(`Question ${i + 1}: Please provide a correct answer.`)
      return
    }
  }

  // Prepare form data
  form.questions = questions.value.map(q => {
    let correctAnswer = q.correct_answer
    const filteredOptions = q.type === 'multiple_choice' ? q.options.filter(opt => opt.trim()) : []
    
    // For multiple choice, convert index to actual option text
    if (q.type === 'multiple_choice' && typeof q.correct_answer === 'string') {
      const optionIndex = parseInt(q.correct_answer)
      if (!isNaN(optionIndex) && filteredOptions[optionIndex]) {
        correctAnswer = filteredOptions[optionIndex]
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

  form.post(route('instructor.quizzes.store', props.classlist.id), {
    onSuccess: () => {
      toast.success('Quiz created successfully.')
      emit('update:open', false)
      form.reset()
      questions.value = [{
        question_text: '',
        type: 'multiple_choice',
        points: 1,
        options: ['', '', '', ''],
        correct_answer: '',
        explanation: '',
      }]
    },
    onError: () => toast.error('Unable to create quiz.'),
    onFinish: () => form.clearErrors(),
  })
}
</script>

<template>
  <Sheet :open="props.open" @update:open="emit('update:open', $event)">
    <SheetContent class="!p-0 sm:max-w-3xl overflow-y-auto">
      <div class="flex h-full flex-col">
        <div class="px-6 pt-6">
          <SheetHeader class="text-left">
            <SheetTitle>Create Quiz</SheetTitle>
            <SheetDescription>Create a quiz with multiple questions. Students can take it multiple times.</SheetDescription>
          </SheetHeader>
        </div>

        <Separator class="my-4" />

        <div class="flex-1 overflow-y-auto px-6 pb-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info -->
            <div class="space-y-4">
              <div class="space-y-2">
                <Label for="title">Quiz Title <span class="text-destructive">*</span></Label>
                <Input id="title" v-model="form.title" placeholder="e.g., Chapter 1 Quiz" required />
                <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                  <AlertDescription>{{ form.errors.title }}</AlertDescription>
                </Alert>
              </div>

              <div class="space-y-2">
                <Label for="description">Description</Label>
                <RichTextEditor v-model="form.description" placeholder="Quiz instructions or description..." min-height="160px" />
              </div>

              <div v-if="props.other_classlists.length > 0" class="space-y-3">
                <Label class="text-sm font-medium">Also post to other classes</Label>
                <div class="grid gap-2 rounded-lg border bg-muted/30 p-3">
                  <label
                    v-for="cls in props.other_classlists"
                    :key="cls.id"
                    class="flex items-center gap-3 rounded-md px-2 py-1.5 hover:bg-muted/60"
                  >
                    <Checkbox
                      :checked="form.also_classlist_ids.includes(cls.id)"
                      @update:checked="(val) => {
                        if (val) form.also_classlist_ids.push(cls.id)
                        else form.also_classlist_ids = form.also_classlist_ids.filter(id => id !== cls.id)
                      }"
                    />
                    <span class="text-sm">
                      {{ cls.name }} <span class="text-muted-foreground">• Room {{ cls.room }}</span>
                    </span>
                  </label>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                  <Label for="start_date">Start Date</Label>
                  <Input id="start_date" type="datetime-local" v-model="form.start_date" />
                </div>
                <div class="space-y-2">
                  <Label for="end_date">End Date</Label>
                  <Input id="end_date" type="datetime-local" v-model="form.end_date" />
                </div>
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div>
                  <div class="font-medium">Shuffle Questions</div>
                  <p class="text-xs text-muted-foreground">Randomize question order for each attempt</p>
                </div>
                <Switch v-model="form.shuffle_questions" />
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div>
                  <div class="font-medium">Show Correct Answers</div>
                  <p class="text-xs text-muted-foreground">Display correct answers after submission</p>
                </div>
                <Switch v-model="form.show_correct_answers" />
              </div>

              <div class="flex items-center justify-between rounded-lg border p-4">
                <div>
                  <div class="font-medium">Publish Quiz</div>
                  <p class="text-xs text-muted-foreground">Make quiz available to students</p>
                </div>
                <Switch v-model="form.is_published" />
              </div>
            </div>

            <Separator />

            <!-- Questions -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <Label class="text-base font-semibold">Questions</Label>
                <Button type="button" variant="outline" size="sm" @click="addQuestion">
                  <Plus class="mr-2 h-4 w-4" /> Add Question
                </Button>
              </div>

              <div v-for="(question, qIndex) in questions" :key="qIndex" class="rounded-lg border p-4 space-y-4">
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-2">
                    <GripVertical class="h-5 w-5 text-muted-foreground" />
                    <span class="font-medium">Question {{ qIndex + 1 }}</span>
                  </div>
                  <Button
                    v-if="questions.length > 1"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeQuestion(qIndex)"
                  >
                    <Trash2 class="h-4 w-4 text-destructive" />
                  </Button>
                </div>

                <div class="space-y-2">
                  <Label>Question Text <span class="text-destructive">*</span></Label>
                  <Textarea v-model="question.question_text" rows="2" placeholder="Enter your question..." required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <Label>Question Type <span class="text-destructive">*</span></Label>
                    <Select v-model="question.type">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                        <SelectItem value="true_false">True/False</SelectItem>
                        <SelectItem value="short_answer">Short Answer</SelectItem>
                        <SelectItem value="essay">Essay</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label>Points <span class="text-destructive">*</span></Label>
                    <Input type="number" min="1" v-model.number="question.points" />
                  </div>
                </div>

                <!-- Multiple Choice Options -->
                <div v-if="question.type === 'multiple_choice'" class="space-y-2">
                  <Label>Options <span class="text-destructive">*</span></Label>
                  <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center gap-2">
                    <RadioGroup 
                      :model-value="question.correct_answer === option ? option : ''"
                      @update:model-value="question.correct_answer = $event"
                      class="flex items-center"
                    >
                      <RadioGroupItem :value="option" :id="`q${qIndex}-opt${oIndex}`" />
                    </RadioGroup>
                    <Input
                      :id="`q${qIndex}-opt${oIndex}`"
                      :model-value="option"
                      :placeholder="`Option ${oIndex + 1}`"
                      @input="(e) => {
                        const newValue = (e.target as HTMLInputElement).value
                        updateQuestionOption(qIndex, oIndex, newValue)
                        // If this was the correct answer, update it
                        if (question.correct_answer === option) {
                          question.correct_answer = newValue
                        }
                      }"
                    />
                    <Button
                      v-if="question.options.length > 2"
                      type="button"
                      variant="ghost"
                      size="sm"
                      @click="() => {
                        // If removing the correct answer, clear it
                        if (question.correct_answer === option) {
                          question.correct_answer = ''
                        }
                        removeOption(qIndex, oIndex)
                      }"
                    >
                      <X class="h-4 w-4" />
                    </Button>
                  </div>
                  <Button type="button" variant="outline" size="sm" @click="addOption(qIndex)">
                    <Plus class="mr-2 h-4 w-4" /> Add Option
                  </Button>
                </div>

                <!-- True/False -->
                <div v-if="question.type === 'true_false'" class="space-y-2">
                  <Label>Correct Answer <span class="text-destructive">*</span></Label>
                  <RadioGroup v-model="question.correct_answer">
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem value="true" id="true" />
                      <Label for="true">True</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem value="false" id="false" />
                      <Label for="false">False</Label>
                    </div>
                  </RadioGroup>
                </div>

                <!-- Short Answer / Essay -->
                <div v-if="question.type === 'short_answer' || question.type === 'essay'" class="space-y-2">
                  <Label>Correct Answer <span class="text-muted-foreground">(for auto-grading)</span></Label>
                  <Textarea
                    v-model="question.correct_answer"
                    :rows="question.type === 'essay' ? 4 : 2"
                    placeholder="Enter correct answer(s), separated by commas for multiple acceptable answers"
                  />
                </div>

                <div class="space-y-2">
                  <Label>Explanation (optional)</Label>
                  <Textarea v-model="question.explanation" rows="2" placeholder="Explanation shown after submission..." />
                </div>
              </div>

              <Alert v-if="questions.length === 0" variant="default">
                <AlertDescription>Add at least one question to create the quiz.</AlertDescription>
              </Alert>
            </div>
          </form>
        </div>

        <!-- Sticky footer -->
        <div class="sticky inset-x-0 bottom-0 border-t bg-background/80 backdrop-blur">
          <div class="px-6 py-4">
            <SheetFooter class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <SheetClose as-child>
                <Button type="button" variant="outline" :disabled="processing">
                  <X class="mr-2 h-4 w-4" /> Close
                </Button>
              </SheetClose>
              <Button type="submit" :disabled="processing || questions.length === 0" @click="submit">
                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ processing ? 'Creating...' : 'Create Quiz' }}
              </Button>
            </SheetFooter>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>
