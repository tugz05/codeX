<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Switch } from '@/components/ui/switch'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter, SheetClose
} from '@/components/ui/sheet'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2, Save, X, FileText } from 'lucide-vue-next'
import Dropzone from '@/components/Dropzone.vue' // <-- drag & drop component from earlier


const props = defineProps<{
  open: boolean
  classlist: { id: string; name: string; room: string; academic_year: string }
}>()

const emit = defineEmits<{ (e:'update:open', v:boolean):void }>()

/** ---- Criteria options (fetched from /instructor/criteria/options) ---- */
type CriteriaOption = {
  id: number
  title: string
  total_points: number
  grading_method: 'sum'|'average'|'custom'
}
const criteriaOptions = ref<CriteriaOption[]>([])
const selectedCriteriaId = ref<number | null>(null)
const selectedCriteria = computed(() =>
  criteriaOptions.value.find(c => c.id === selectedCriteriaId.value) || null
)

onMounted(async () => {
  try {
    const res = await fetch(route('instructor.criteria.options'), { headers: { Accept: 'application/json' } })
    criteriaOptions.value = await res.json()
  } catch (e) {
    // Non-fatal—user can still post without criteria
    console.warn('Unable to fetch criteria options', e)
  }
})

/** ---- Form ---- */
const form = useForm({
  title: '',
  instruction: '',
  points: null as number | null,
  due_date: '' as string | null,
  due_time: '' as string | null,
  accessible_date: '' as string | null,
  accessible_time: '' as string | null,
  attachments: [] as File[],
  criteria_id: null as number | null, // <--- NEW
})

const accessToggle = ref<boolean>(false)
watch(accessToggle, (on) => {
  if (!on) {
    form.accessible_date = ''
    form.accessible_time = ''
  }
})

const onFiles = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (input.files) form.attachments = Array.from(input.files)
}

const processing = computed(() => form.processing)

/** Keep form.criteria_id in sync with UI select */
watch(selectedCriteriaId, (v) => {
  form.criteria_id = v ?? null
})

function submit() {
  form.post(route('instructor.activities.store', props.classlist.id), {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Activity created.')
      emit('update:open', false)
      // reset for next open
      form.reset()
      accessToggle.value = false
      selectedCriteriaId.value = null
    },
    onError: () => toast.error('Unable to create activity.'),
    onFinish: () => form.clearErrors(),
  })
}
</script>

<template>
  <Sheet :open="props.open" @update:open="emit('update:open', $event)">
    <SheetContent class="!p-0 sm:max-w-xl">
      <div class="flex h-full flex-col">
        <div class="px-6 pt-6">
          <SheetHeader class="text-left">
            <SheetTitle>Create Activity</SheetTitle>
            <SheetDescription>Post an assignment/announcement, attach files, set due dates, and apply a criteria.</SheetDescription>
          </SheetHeader>
        </div>

        <Separator class="my-4" />

        <div class="flex-1 overflow-y-auto px-6 pb-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Title -->
            <div class="space-y-2">
              <Label for="title">Title</Label>
              <Input id="title" v-model="form.title" placeholder="e.g., Programming Exercise 1" required />
              <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors.title }}</AlertDescription>
              </Alert>
            </div>

            <!-- Instruction -->
            <div class="space-y-2">
              <Label for="instruction">Instruction</Label>
              <Textarea id="instruction" v-model="form.instruction" rows="6" placeholder="Write clear instructions, rubric details, or links…" />
              <Alert v-if="form.errors.instruction" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors.instruction }}</AlertDescription>
              </Alert>
            </div>

            <!-- Points / Due -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div class="space-y-2">
                <Label for="points">Points</Label>
                <Input id="points" type="number" min="0" v-model.number="form.points" placeholder="100" />
                <Alert v-if="form.errors.points" variant="destructive" class="mt-2">
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>{{ form.errors.points }}</AlertDescription>
                </Alert>
              </div>
              <div class="space-y-2">
                <Label for="due_date">Due date</Label>
                <Input id="due_date" type="date" v-model="form.due_date" />
                <Alert v-if="form.errors.due_date" variant="destructive" class="mt-2">
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>{{ form.errors.due_date }}</AlertDescription>
                </Alert>
              </div>
              <div class="space-y-2">
                <Label for="due_time">Due time</Label>
                <Input id="due_time" type="time" v-model="form.due_time" />
                <Alert v-if="form.errors.due_time" variant="destructive" class="mt-2">
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>{{ form.errors.due_time }}</AlertDescription>
                </Alert>
              </div>
            </div>

            <Separator />

            <!-- Criteria Select -->
            <div class="space-y-2">
              <Label for="criteria">Criteria</Label>
              <Select v-model="selectedCriteriaId">
                <SelectTrigger id="criteria" class="w-full">
                  <SelectValue placeholder="Choose criteria (optional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in criteriaOptions" :key="opt.id" :value="opt.id">
                    {{ opt.title }} · {{ opt.total_points }} pts ({{ opt.grading_method }})
                  </SelectItem>
                  <SelectItem :value="null as any">No criteria</SelectItem>
                </SelectContent>
              </Select>
              <Alert v-if="form.errors.criteria_id" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors.criteria_id }}</AlertDescription>
              </Alert>

              <!-- Small summary -->
              <div v-if="selectedCriteria" class="mt-2 inline-flex items-center gap-2 rounded-md border px-3 py-2 text-xs">
                <FileText class="h-4 w-4" />
                <span class="font-medium">{{ selectedCriteria.title }}</span>
                <span class="text-muted-foreground">• {{ selectedCriteria.total_points }} pts • {{ selectedCriteria.grading_method }}</span>
              </div>
            </div>

            <Separator />

            <!-- Access window toggle -->
            <div class="flex items-center justify-between rounded-lg border p-4">
              <div>
                <div class="font-medium">Schedule access window</div>
                <p class="text-xs text-muted-foreground">Limit when students can access this activity. Turn on to set the date/time.</p>
              </div>
              <Switch v-model="accessToggle" />
            </div>

            <!-- Access fields (conditional) -->
            <transition name="fade">
              <div v-if="accessToggle" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="accessible_date">Accessible date</Label>
                  <Input id="accessible_date" type="date" v-model="form.accessible_date" />
                  <Alert v-if="form.errors.accessible_date" variant="destructive" class="mt-2">
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{{ form.errors.accessible_date }}</AlertDescription>
                  </Alert>
                </div>
                <div class="space-y-2">
                  <Label for="accessible_time">Accessible time</Label>
                  <Input id="accessible_time" type="time" v-model="form.accessible_time" />
                  <Alert v-if="form.errors.accessible_time" variant="destructive" class="mt-2">
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{{ form.errors.accessible_time }}</AlertDescription>
                  </Alert>
                </div>
              </div>
            </transition>

            <Separator />

            <!-- Attachments -->
            <div class="space-y-2">
              <Label>Add more files</Label>
              <Dropzone
                v-model="form.attachments"
                :accept="['image/*','video/mp4','.pdf']"
                :max-size-m-b="50"
                :multiple="true"
              />
              <p v-if="form.attachments?.length" class="mt-1 text-xs text-muted-foreground">
                {{ form.attachments.length }} file(s) selected
              </p>
              <Alert v-if="form.errors['attachments.0']" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors['attachments.0'] }}</AlertDescription>
              </Alert>
            </div>
          </form>
        </div>

        <!-- Sticky footer -->
        <div class="sticky inset-x-0 bottom-0 border-t bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
          <div class="px-6 py-4">
            <SheetFooter class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <SheetClose as-child>
                <Button type="button" variant="outline" :disabled="processing">
                  <X class="mr-2 h-4 w-4" /> Close
                </Button>
              </SheetClose>
              <Button type="submit" :disabled="processing" @click="submit">
                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ processing ? 'Saving…' : 'Create Activity' }}
              </Button>
            </SheetFooter>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<style scoped>
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px) }
.fade-enter-active, .fade-leave-active { transition: all .18s ease }
</style>
