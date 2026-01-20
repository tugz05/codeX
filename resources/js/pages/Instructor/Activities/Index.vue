<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import ActivityRowCard from './Partials/ActivityRowCard.vue'
import CreateActivitySheet from './Partials/CreateActivitySheet.vue'
import EditActivitySheet from './Partials/EditActivitySheet.vue'
import { Plus, ArrowLeft, FileText, ClipboardList, GraduationCap, Trash2, Edit, Eye, ChevronDown, Users, Mail, Calendar, Search, ArrowUpDown, Download, BookOpen, EllipsisVertical, NotebookPen } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

const props = defineProps<{
  classlist: {
    id: string
    name: string
    room: string
    academic_year: string
    section?: string | null
    criteriaOptions: Array<{
      id: number
      title: string
      description: string | null
      grading_method: string
    }>
  }
  activities: Array<{
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments: Array<{ id:number; name:string; type:string | null; url:string; size:number | null }>
  }>
  assignments?: Array<{
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments_count: number
  }>
  quizzes?: Array<{
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    is_published: boolean
    questions_count: number
    created_at: string
  }>
  examinations?: Array<{
    id: number
    title: string
    description: string | null
    total_points: number
    time_limit: number | null
    attempts_allowed: number
    is_published: boolean
    require_proctoring: boolean
    questions_count: number
    created_at: string
  }>
  materials?: Array<{
    id: number
    title: string
    description: string | null
    type: string
    url: string | null
    video_url: string | null
    embed_code: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments_count: number
  }>
  students?: Array<{
    id: number
    name: string
    email: string
    joined_at: string | null
    status: string
  }>
  total_students?: number
}>()

const showCreate = ref(false)
const showEdit = ref(false)
const editingActivity = ref<typeof props.activities[number] | null>(null)

// Students tab state
const searchQuery = ref('')
const sortBy = ref<'name' | 'email' | 'joined_at'>('name')
const sortOrder = ref<'asc' | 'desc'>('asc')

// Combine all items into one sorted list
const allItems = computed(() => {
  const items: Array<{
    id: number
    type: 'assignment' | 'activity' | 'quiz' | 'examination' | 'material'
    title: string
    description?: string | null
    instruction?: string | null
    points?: number | null
    total_points?: number
    created_at: string
    [key: string]: any
  }> = []

  // Add assignments
  ;(props.assignments || []).forEach(assignment => {
    items.push({
      ...assignment,
      type: 'assignment' as const,
      description: assignment.instruction || null, // Map instruction to description for consistency
    } as any)
  })

  // Add activities
  props.activities.forEach(activity => {
    items.push({
      ...activity,
      type: 'activity' as const,
      description: activity.instruction || null, // Map instruction to description for consistency
    } as any)
  })

  // Add quizzes
  ;(props.quizzes || []).forEach(quiz => {
    items.push({
      ...quiz,
      type: 'quiz' as const,
    })
  })

  // Add examinations
  ;(props.examinations || []).forEach(exam => {
    items.push({
      ...exam,
      type: 'examination' as const,
    })
  })

  // Add materials
  ;(props.materials || []).forEach(material => {
    items.push({
      ...material,
      type: 'material' as const,
    })
  })

  // Sort by created_at (newest first)
  return items.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

// Delete confirm
const showDeleteConfirm = ref(false)
const deletingId = ref<number | null>(null)
const deletingType = ref<'assignment' | 'activity' | 'quiz' | 'examination' | 'material'>('activity')
const confirmDelete = (id: number, type: 'assignment' | 'activity' | 'quiz' | 'examination' | 'material' = 'activity') => {
  deletingId.value = id
  deletingType.value = type
  showDeleteConfirm.value = true
}
const doDelete = () => {
  if (!deletingId.value) return

  let routeName = ''
  if (deletingType.value === 'assignment') {
    routeName = 'instructor.assignments.destroy'
  } else if (deletingType.value === 'activity') {
    routeName = 'instructor.activities.destroy'
  } else if (deletingType.value === 'quiz') {
    routeName = 'instructor.quizzes.destroy'
  } else if (deletingType.value === 'examination') {
    routeName = 'instructor.examinations.destroy'
  } else if (deletingType.value === 'material') {
    routeName = 'instructor.materials.destroy'
  }

  router.delete(route(routeName, [props.classlist.id, deletingId.value]), {
    onSuccess: () => {
      const typeNames: Record<string, string> = {
        assignment: 'Assignment',
        activity: 'Activity',
        quiz: 'Quiz',
        examination: 'Examination',
        material: 'Material'
      }
      toast.success(`${typeNames[deletingType.value] || 'Item'} deleted.`)
      showDeleteConfirm.value = false
      deletingId.value = null
    },
    onError: () => toast.error(`Failed to delete ${deletingType.value}.`)
  })
}

const onEdit = (activity: typeof props.activities[number] | any) => {
  editingActivity.value = activity as typeof props.activities[number]
  showEdit.value = true
}

// Back button action
const goBack = () => {
  router.visit(route('instructor.classlist'))
}

function formatDate(value?: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleDateString()
}

function formatTime(value?: string | null): string {
  if (!value) return ''
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
    const [hh, mm] = value.split(':')
    const d = new Date()
    d.setHours(Number(hh), Number(mm), 0, 0)
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  }
  return value
}

function isAssignmentAccessible(assignment: any): boolean {
  if (!assignment.accessible_date) return true

  const now = new Date()
  const accessibleDate = new Date(assignment.accessible_date)

  if (assignment.accessible_time) {
    const [hours, minutes] = assignment.accessible_time.split(':')
    accessibleDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    accessibleDate.setHours(0, 0, 0, 0)
  }

  return now >= accessibleDate
}

function isMaterialAccessible(material: any): boolean {
  if (!material.accessible_date) return true

  const now = new Date()
  const accessibleDate = new Date(material.accessible_date)

  if (material.accessible_time) {
    const [hours, minutes] = material.accessible_time.split(':')
    accessibleDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    accessibleDate.setHours(0, 0, 0, 0)
  }

  return now >= accessibleDate
}

function formatDateTime(dateString: string | null): string {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Filtered and sorted students
const filteredStudents = computed(() => {
  if (!props.students) return []

  let filtered = [...props.students]

  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtered = filtered.filter(student =>
      student.name.toLowerCase().includes(query) ||
      student.email.toLowerCase().includes(query)
    )
  }

  // Sort
  filtered.sort((a, b) => {
    let aVal: string | null = ''
    let bVal: string | null = ''

    if (sortBy.value === 'name') {
      aVal = a.name.toLowerCase()
      bVal = b.name.toLowerCase()
    } else if (sortBy.value === 'email') {
      aVal = a.email.toLowerCase()
      bVal = b.email.toLowerCase()
    } else if (sortBy.value === 'joined_at') {
      aVal = a.joined_at || ''
      bVal = b.joined_at || ''
    }

    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
    return 0
  })

  return filtered
})

const toggleSort = (field: 'name' | 'email' | 'joined_at') => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortOrder.value = 'asc'
  }
}
</script>

<template>
  <Head :title="`Class Content · ${props.classlist.name}`" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-3 sm:gap-4 rounded-xl p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" @click="goBack">
            <ArrowLeft class="h-4 w-4 mr-1" /> Back
          </Button>
          <div>
            <h1 class="text-xl font-semibold">Class Content</h1>
            <p class="text-sm text-muted-foreground">
              {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
            </p>
          </div>
        </div>
        <!-- Dropdown Create Button -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button>
              <Plus class="mr-2 h-4 w-4" /> Create
              <ChevronDown class="ml-2 h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem as-child>
              <Link :href="route('instructor.assignments.create', props.classlist.id)">
                <NotebookPen class="mr-2 h-4 w-4" />
                <span>Assignment</span>
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem @click="showCreate = true">
              <FileText class="mr-2 h-4 w-4" />
              <span>Activity</span>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
              <Link :href="route('instructor.quizzes.create', props.classlist.id)">
                <ClipboardList class="mr-2 h-4 w-4" />
                <span>Quiz</span>
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
              <Link :href="route('instructor.examinations.create', props.classlist.id)">
                <GraduationCap class="mr-2 h-4 w-4" />
                <span>Examination</span>
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
              <Link :href="route('instructor.materials.create', props.classlist.id)">
                <BookOpen class="mr-2 h-4 w-4" />
                <span>Material</span>
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <!-- Tabs -->
      <Tabs default-value="content" class="w-full">
        <TabsList class="grid w-full grid-cols-2 border-2">
          <TabsTrigger value="content">Content</TabsTrigger>
          <TabsTrigger value="students">Students</TabsTrigger>
        </TabsList>

        <!-- Content Tab -->
        <TabsContent value="content" class="mt-4">
          <!-- Unified List -->
          <div class="space-y-3">
        <!-- Activities -->
        <template v-for="item in allItems" :key="`${item.type}-${item.id}`">
          <!-- Assignment Card -->
          <div
            v-if="item.type === 'assignment'"
            class="w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 p-4 hover:shadow-md transition-shadow cursor-pointer"
          >
            <div class="flex items-start justify-between">
              <Link
                :href="route('instructor.assignments.show', [props.classlist.id, item.id])"
                class="space-y-1 flex-1 min-w-0"
              >
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                    <NotebookPen class="inline h-3 w-3 mr-1" /> Assignment
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="!item.accessible_date"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available
                  </span>
                  <span
                    v-else-if="isAssignmentAccessible(item)"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available Now
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
                  >
                    Scheduled
                  </span>
                </div>
                <p v-if="item.instruction" class="text-sm text-muted-foreground line-clamp-3">
                  {{ item.instruction }}
                </p>
                <div class="text-xs text-muted-foreground flex flex-wrap gap-3">
                  <span>Created: {{ formatDate(item.created_at) }}</span>
                  <span v-if="item.points !== null">• {{ item.points }} pts</span>
                  <span v-if="item.due_date">• Due: {{ formatDate(item.due_date) }}<span v-if="item.due_time"> at {{ formatTime(item.due_time) }}</span></span>
                  <span v-if="item.attachments_count">• {{ item.attachments_count }} attachment{{ item.attachments_count !== 1 ? 's' : '' }}</span>
                </div>
              </Link>
              <!-- Ellipsis menu -->
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <button 
                    class="text-gray-500 transition-colors hover:text-gray-900 dark:text-neutral-400 dark:hover:text-white"
                    @click.stop
                  >
                    <EllipsisVertical class="h-5 w-5" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.assignments.edit', [props.classlist.id, item.id])">
                      <Edit class="mr-2 h-4 w-4" />
                      Edit
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.assignments.show', [props.classlist.id, item.id])">
                      <Eye class="mr-2 h-4 w-4" />
                      View
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="confirmDelete(item.id, 'assignment')">
                    <Trash2 class="mr-2 h-4 w-4" />
                    <span class="text-red-500">Delete</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <!-- Activity Card -->
          <ActivityRowCard
            v-else-if="item.type === 'activity'"
            :activity="item as unknown as typeof props.activities[number]"
            :classlist-id="props.classlist.id"
            @edit="onEdit"
            @delete="(id) => confirmDelete(id, 'activity')"
          />

          <!-- Quiz Card -->
          <div
            v-else-if="item.type === 'quiz'"
            class="w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 p-4 hover:shadow-md transition-shadow cursor-pointer"
          >
            <div class="flex items-start justify-between">
              <Link
                :href="route('instructor.quizzes.show', [props.classlist.id, item.id])"
                class="space-y-1 flex-1 min-w-0"
              >
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <ClipboardList class="inline h-3 w-3 mr-1" /> Quiz
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="item.is_published"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Published
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300"
                  >
                    Draft
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-3">
                  {{ item.description }}
                </p>
                <div class="text-xs text-muted-foreground flex flex-wrap gap-3">
                  <span>Created: {{ formatDate(item.created_at) }}</span>
                  <span v-if="item.total_points">• {{ item.total_points }} points</span>
                  <span v-if="item.time_limit">• {{ item.time_limit }} minutes</span>
                  <span>• {{ item.questions_count }} questions</span>
                  <span>• {{ item.attempts_allowed }} attempt{{ item.attempts_allowed !== 1 ? 's' : '' }}</span>
                </div>
              </Link>
              <!-- Ellipsis menu -->
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <button 
                    class="text-gray-500 transition-colors hover:text-gray-900 dark:text-neutral-400 dark:hover:text-white"
                    @click.stop
                  >
                    <EllipsisVertical class="h-5 w-5" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.quizzes.edit', [props.classlist.id, item.id])">
                      <Edit class="mr-2 h-4 w-4" />
                      Edit
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.quizzes.show', [props.classlist.id, item.id])">
                      <Eye class="mr-2 h-4 w-4" />
                      View
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="confirmDelete(item.id, 'quiz')">
                    <Trash2 class="mr-2 h-4 w-4" />
                    <span class="text-red-500">Delete</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <!-- Examination Card -->
          <div
            v-else-if="item.type === 'examination'"
            class="w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 p-4 hover:shadow-md transition-shadow cursor-pointer"
          >
            <div class="flex items-start justify-between">
              <Link
                :href="route('instructor.examinations.show', [props.classlist.id, item.id])"
                class="space-y-1 flex-1 min-w-0"
              >
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                    <GraduationCap class="inline h-3 w-3 mr-1" /> Examination
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="item.is_published"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Published
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300"
                  >
                    Draft
                  </span>
                  <span
                    v-if="item.require_proctoring"
                    class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                  >
                    Proctored
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-3">
                  {{ item.description }}
                </p>
                <div class="text-xs text-muted-foreground flex flex-wrap gap-3">
                  <span>Created: {{ formatDate(item.created_at) }}</span>
                  <span v-if="item.total_points">• {{ item.total_points }} points</span>
                  <span v-if="item.time_limit">• {{ item.time_limit }} minutes</span>
                  <span>• {{ item.questions_count }} questions</span>
                  <span>• {{ item.attempts_allowed }} attempt{{ item.attempts_allowed !== 1 ? 's' : '' }}</span>
                </div>
              </Link>
              <!-- Ellipsis menu -->
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <button 
                    class="text-gray-500 transition-colors hover:text-gray-900 dark:text-neutral-400 dark:hover:text-white"
                    @click.stop
                  >
                    <EllipsisVertical class="h-5 w-5" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.examinations.edit', [props.classlist.id, item.id])">
                      <Edit class="mr-2 h-4 w-4" />
                      Edit
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.examinations.show', [props.classlist.id, item.id])">
                      <Eye class="mr-2 h-4 w-4" />
                      View
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="confirmDelete(item.id, 'examination')">
                    <Trash2 class="mr-2 h-4 w-4" />
                    <span class="text-red-500">Delete</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <!-- Material Card -->
          <div
            v-else-if="item.type === 'material'"
            class="w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 p-4 hover:shadow-md transition-shadow cursor-pointer"
          >
            <div class="flex items-start justify-between">
              <Link
                :href="route('instructor.materials.show', [props.classlist.id, item.id])"
                class="space-y-1 flex-1 min-w-0"
              >
                <div class="flex items-center gap-2 mb-2">
                  <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <BookOpen class="inline h-3 w-3 mr-1" /> Material
                  </span>
                  <h3 class="text-base font-semibold">{{ item.title }}</h3>
                  <span
                    v-if="!item.accessible_date"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available
                  </span>
                  <span
                    v-else-if="isMaterialAccessible(item)"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                  >
                    Available Now
                  </span>
                  <span
                    v-else
                    class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
                  >
                    Scheduled
                  </span>
                </div>
                <p v-if="item.description" class="text-sm text-muted-foreground line-clamp-3">
                  {{ item.description }}
                </p>
                <div class="text-xs text-muted-foreground flex flex-wrap gap-3">
                  <span>Created: {{ formatDate(item.created_at) }}</span>
                  <span class="capitalize">• {{ item.type }}</span>
                  <span v-if="item.attachments_count">• {{ item.attachments_count }} attachment{{ item.attachments_count !== 1 ? 's' : '' }}</span>
                  <span v-if="item.accessible_date">• Accessible: {{ formatDate(item.accessible_date) }}</span>
                </div>
              </Link>
              <!-- Ellipsis menu -->
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <button 
                    class="text-gray-500 transition-colors hover:text-gray-900 dark:text-neutral-400 dark:hover:text-white"
                    @click.stop
                  >
                    <EllipsisVertical class="h-5 w-5" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.materials.edit', [props.classlist.id, item.id])">
                      <Edit class="mr-2 h-4 w-4" />
                      Edit
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link :href="route('instructor.materials.show', [props.classlist.id, item.id])">
                      <Eye class="mr-2 h-4 w-4" />
                      View
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="confirmDelete(item.id, 'material')">
                    <Trash2 class="mr-2 h-4 w-4" />
                    <span class="text-red-500">Delete</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <div v-if="allItems.length === 0" class="rounded-xl border p-6 text-center text-sm text-muted-foreground">
          No content yet. Click "Create" to add an Activity, Quiz, Examination, or Material.
        </div>
          </div>
        </TabsContent>

        <!-- Students Tab -->
        <TabsContent value="students" class="mt-6">
          <div class="space-y-6">
            <!-- Header Stats Card -->
            <Card class="border-2">
              <CardContent class="pt-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                  <div class="text-center md:text-left">
                    <p class="text-sm font-medium text-muted-foreground mb-1">Total Students</p>
                    <p class="text-3xl font-bold">{{ props.total_students || 0 }}</p>
                  </div>
                  <div class="text-center md:text-left">
                    <p class="text-sm font-medium text-muted-foreground mb-1">Active</p>
                    <p class="text-3xl font-bold">{{ props.students?.filter(s => s.status === 'active').length || 0 }}</p>
                  </div>
                  <div class="text-center md:text-left">
                    <p class="text-sm font-medium text-muted-foreground mb-1">Showing</p>
                    <p class="text-3xl font-bold">{{ filteredStudents.length }}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Search and Filter Bar -->
            <Card class="border-2" v-if="props.students && props.students.length > 0">
              <CardContent class="pt-6">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                  <div class="relative flex-1 w-full sm:max-w-sm">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      v-model="searchQuery"
                      placeholder="Search by name or email..."
                      class="pl-10 border-2"
                    />
                  </div>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="outline" class="border-2">
                        <ArrowUpDown class="mr-2 h-4 w-4" />
                        Sort
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="toggleSort('name')">
                        <span>Name</span>
                        <span v-if="sortBy === 'name'" class="ml-2">
                          {{ sortOrder === 'asc' ? '↑' : '↓' }}
                        </span>
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="toggleSort('email')">
                        <span>Email</span>
                        <span v-if="sortBy === 'email'" class="ml-2">
                          {{ sortOrder === 'asc' ? '↑' : '↓' }}
                        </span>
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="toggleSort('joined_at')">
                        <span>Join Date</span>
                        <span v-if="sortBy === 'joined_at'" class="ml-2">
                          {{ sortOrder === 'asc' ? '↑' : '↓' }}
                        </span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </CardContent>
            </Card>

            <!-- Students List -->
            <Card class="border-2">
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Users class="h-5 w-5" />
                  Student Roster
                </CardTitle>
                <CardDescription>
                  Complete list of enrolled students in this class
                </CardDescription>
              </CardHeader>
              <CardContent>
                <!-- Empty State -->
                <div v-if="!props.students || props.students.length === 0" class="text-center py-16">
                  <div class="inline-flex items-center justify-center w-20 h-20 border-2 rounded-full mb-4">
                    <Users class="h-10 w-10 opacity-50" />
                  </div>
                  <h3 class="text-xl font-semibold mb-2">No Students Enrolled</h3>
                  <p class="text-sm text-muted-foreground max-w-md mx-auto">
                    Students can join this class using the class code. Once they join, they will appear here.
                  </p>
                </div>

                <!-- No Results from Search -->
                <div v-else-if="filteredStudents.length === 0" class="text-center py-12">
                  <Search class="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <h3 class="text-lg font-semibold mb-2">No Results Found</h3>
                  <p class="text-sm text-muted-foreground">
                    No students match your search criteria. Try a different search term.
                  </p>
                </div>

                <!-- Students Table -->
                <div v-else class="space-y-2">
                  <!-- Desktop Table View -->
                  <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                      <thead>
                        <tr class="border-b-2">
                          <th class="text-left py-3 px-4 font-semibold text-sm">#</th>
                          <th class="text-left py-3 px-4 font-semibold text-sm">Student Name</th>
                          <th class="text-left py-3 px-4 font-semibold text-sm">Email</th>
                          <th class="text-left py-3 px-4 font-semibold text-sm">Join Date</th>
                          <th class="text-left py-3 px-4 font-semibold text-sm">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="(student, index) in filteredStudents"
                          :key="student.id"
                          class="border-b hover:bg-muted/50 transition-colors"
                        >
                          <td class="py-4 px-4 font-medium">{{ index + 1 }}</td>
                          <td class="py-4 px-4">
                            <div class="font-semibold">{{ student.name }}</div>
                          </td>
                          <td class="py-4 px-4">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                              <Mail class="h-3 w-3" />
                              <span>{{ student.email }}</span>
                            </div>
                          </td>
                          <td class="py-4 px-4">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                              <Calendar class="h-3 w-3" />
                              <span>{{ formatDateTime(student.joined_at) }}</span>
                            </div>
                          </td>
                          <td class="py-4 px-4">
                            <Badge v-if="student.status === 'active'" variant="outline" class="text-xs">
                              Active
                            </Badge>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Mobile Card View -->
                  <div class="md:hidden space-y-3">
                    <div
                      v-for="(student, index) in filteredStudents"
                      :key="student.id"
                      class="p-4 border-2 rounded-lg"
                    >
                      <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                          <div class="flex items-center justify-center w-8 h-8 border-2 rounded-full font-semibold text-sm">
                            {{ index + 1 }}
                          </div>
                          <div>
                            <p class="font-semibold text-base">{{ student.name }}</p>
                            <Badge v-if="student.status === 'active'" variant="outline" class="text-xs mt-1">
                              Active
                            </Badge>
                          </div>
                        </div>
                      </div>
                      <div class="space-y-2 pl-11">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                          <Mail class="h-3 w-3" />
                          <span class="break-all">{{ student.email }}</span>
                        </div>
                        <div v-if="student.joined_at" class="flex items-center gap-2 text-sm text-muted-foreground">
                          <Calendar class="h-3 w-3" />
                          <span>Joined {{ formatDateTime(student.joined_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>

    <!-- Create Sheet -->
    <CreateActivitySheet
      v-model:open="showCreate"
      :classlist="props.classlist"
    />

    <!-- Edit Sheet -->
    <EditActivitySheet
      v-if="editingActivity"
      v-model:open="showEdit"
      :classlist="props.classlist"
      :activity="editingActivity"
      @closed="editingActivity = null"
      :criteriaOptions="props.classlist.criteriaOptions"
    />

    <!-- Delete Confirm -->
    <Dialog v-model:open="showDeleteConfirm">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>
            Delete {{ deletingType === 'activity' ? 'activity' : deletingType === 'quiz' ? 'quiz' : 'examination' }}?
          </DialogTitle>
        </DialogHeader>
        <p class="text-sm text-muted-foreground">This action cannot be undone.</p>
        <DialogFooter class="gap-2 sm:justify-end">
          <Button variant="outline" @click="showDeleteConfirm = false">Cancel</Button>
          <Button variant="destructive" @click="doDelete">Yes, Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
