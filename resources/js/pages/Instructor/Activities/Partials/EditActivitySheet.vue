<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
  Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter, SheetClose
} from '@/components/ui/sheet'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Loader2, Save, X, FileText } from 'lucide-vue-next'
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import Dropzone from '@/components/Dropzone.vue'

type CriteriaOption = {
  id: number
  title: string
  total_points: number
  grading_method: 'sum' | 'average' | 'custom'
}

const props = defineProps<{
  open: boolean
  classlist: { id: string; name: string; room: string; academic_year: string }
  activity: {
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    // include attachments if you load them in controller -> index()
    attachments?: Array<{ id:number; name:string; url:string; type:string|null }>
    // OPTIONAL: if your controller loads current criteria, expose it like this:
    criteria_id?: number | null
  }
}>()

const emit = defineEmits<{ (e:'update:open', v:boolean):void, (e:'closed'):void }>()

/* -------------------- Criteria options & selection -------------------- */
const criteriaOptions = ref<CriteriaOption[]>([])
const selectedCriteriaId = ref<number | null>(props.activity.criteria_id ?? null)
const selectedCriteria = computed(() =>
  criteriaOptions.value.find(c => c.id === selectedCriteriaId.value) || null
)

onMounted(async () => {
  try {
    const res = await fetch(route('instructor.criteria.options'), { headers: { Accept: 'application/json' } })
    const json = await res.json()
    criteriaOptions.value = Array.isArray(json) ? json : []
    // If activity already has a criteria, keep it selected
    if (typeof props.activity.criteria_id === 'number') {
      selectedCriteriaId.value = props.activity.criteria_id
    }
  } catch (e) {
    console.warn('Unable to fetch criteria options', e)
  }
})

/* -------------------- Files to remove (existing ones) -------------------- */
const toRemove = ref<number[]>([])

/* -------------------- Form -------------------- */
const form = useForm({
  title: props.activity.title ?? '',
  instruction: props.activity.instruction ?? '',
  points: props.activity.points ?? null,
  due_date: props.activity.due_date ?? '',
  due_time: props.activity.due_time ?? '',
  accessible_date: props.activity.accessible_date ?? '',
  accessible_time: props.activity.accessible_time ?? '',
  // NEW: add files when editing
  attachments: [] as File[],
  // NEW: ids of existing attachments to remove
  attachments_remove: [] as number[],
  // NEW: criteria binding
  criteria_id: (props.activity.criteria_id ?? null) as number | null,
})

watch(selectedCriteriaId, (v) => {
  form.criteria_id = v ?? null
})

const accessToggle = ref<boolean>(!!(form.accessible_date || form.accessible_time))
watch(accessToggle, (on) => { if (!on) { form.accessible_date=''; form.accessible_time='' } })

const processing = computed(() => form.processing)

function submit() {
  form.attachments_remove = toRemove.value
  form.put(route('instructor.activities.update', [props.classlist.id, props.activity.id]), {
    forceFormData: false, // we may be uploading files
    onSuccess: () => { toast.success('Activity updated.'); emit('update:open', false); emit('closed') },
    onError: () => toast.error('Unable to update activity.'),
    onFinish: () => form.clearErrors(),
  })
}
</script>

<template>
  <Sheet :open="props.open" @update:open="emit('update:open', $event)">
    <SheetContent class="sm:max-w-xl !p-0">
      <div class="flex h-full flex-col">
        <div class="px-6 pt-6">
          <SheetHeader class="text-left">
            <SheetTitle>Edit Activity</SheetTitle>
            <SheetDescription>Update details, schedule, attachments, and criteria.</SheetDescription>
          </SheetHeader>
        </div>

        <Separator class="my-4" />

        <div class="flex-1 overflow-y-auto px-6 pb-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Title -->
            <div class="space-y-2">
              <Label for="title">Title</Label>
              <Input id="title" v-model="form.title" required />
              <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors.title }}</AlertDescription>
              </Alert>
            </div>

            <!-- Instruction -->
            <div class="space-y-2">
              <Label for="instruction">Instruction</Label>
              <Textarea id="instruction" v-model="form.instruction" rows="6" />
              <Alert v-if="form.errors.instruction" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors.instruction }}</AlertDescription>
              </Alert>
            </div>

            <!-- Points & Due -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="points">Points</Label>
                <Input id="points" type="number" min="0" v-model.number="form.points" />
                <Alert v-if="form.errors.points" variant="destructive" class="mt-2">
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>{{ form.errors.points }}</AlertDescription>
                </Alert>
              </div>
              <div class="space-y-2">
                <Label for="due_date">Due date</Label>
                <Input id="due_date" type="date" v-model="form.due_date" />
              </div>
              <div class="space-y-2">
                <Label for="due_time">Due time</Label>
                <Input id="due_time" type="time" v-model="form.due_time" />
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

            <!-- Access window -->
            <div class="flex items-center justify-between rounded-lg border p-4">
              <div>
                <div class="font-medium">Schedule access window</div>
                <p class="text-xs text-muted-foreground">Turn on to set a date and time.</p>
              </div>
              <Switch v-model="accessToggle" />
            </div>

            <transition name="fade">
              <div v-if="accessToggle" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="accessible_date">Accessible date</Label>
                  <Input id="accessible_date" type="date" v-model="form.accessible_date" />
                </div>
                <div class="space-y-2">
                  <Label for="accessible_time">Accessible time</Label>
                  <Input id="accessible_time" type="time" v-model="form.accessible_time" />
                </div>
              </div>
            </transition>

            <Separator />

            <!-- Existing attachments (removal) -->
            <div class="space-y-3">
              <Label>Existing attachments</Label>
              <div v-if="activity.attachments?.length" class="rounded-lg border p-3 space-y-2">
                <div v-for="att in activity.attachments" :key="att.id"
                     class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2 text-sm">
                  <a class="truncate underline" :href="att.url" target="_blank">{{ att.name }}</a>
                  <label class="flex select-none items-center gap-2 text-xs">
                    <input type="checkbox" :value="att.id" v-model="toRemove" />
                    Remove
                  </label>
                </div>
              </div>
              <p v-else class="text-xs text-muted-foreground">No attachments yet.</p>
            </div>

            <!-- Add more attachments -->
            <div class="space-y-2">
              <Label>Add more files</Label>
              <Dropzone
                v-model="form.attachments"
                :accept="['image/*','video/mp4','.pdf']"
                :max-size-m-b="50"
                :multiple="true"
              />
              <Alert v-if="form.errors['attachments.0']" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ form.errors['attachments.0'] }}</AlertDescription>
              </Alert>
            </div>
          </form>
        </div>

        <!-- Sticky footer -->
        <div class="sticky bottom-0 inset-x-0 border-t bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
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
                {{ processing ? 'Saving…' : 'Save Changes' }}
              </Button>
            </SheetFooter>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<style scoped>
.fade-enter-from,.fade-leave-to{opacity:0;transform:translateY(-4px)}
.fade-enter-active,.fade-leave-active{transition:all .18s ease}
</style>
