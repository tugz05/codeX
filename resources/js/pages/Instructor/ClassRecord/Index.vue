<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import {
  Plus,
  ArrowLeft,
  Edit,
  Trash2,
  Save,
  Download,
  GripVertical,
  Calculator,
  Trophy,
  FileSpreadsheet,
  ChevronRight
} from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'

interface GradeItem {
  id: number
  name: string
  max_points: number
  date: string | null
  description: string | null
  order: number
}

interface GradeComponent {
  id: number
  name: string
  weight: number
  description: string | null
  order: number
  grade_items: GradeItem[]
}

interface Student {
  id: number
  student_id: string
  first_name: string
  middle_name: string | null
  last_name: string
  suffix: string | null
  email: string
}

interface StudentGrade {
  id?: number
  grade_item_id: number
  user_id: number
  points: number | null
  remarks: string | null
}

interface FinalGrade {
  final_grade: number
  total_weighted_score: number
  letter_grade: string
  breakdown: Array<{
    component: string
    points: number
    max_points: number
    percentage: number
    weighted_score: number
    weight: number
  }>
}

const props = defineProps<{
  classlist: {
    id: string
    name: string
    room: string
    academic_year: string
    section?: string | null
  }
  students: Student[]
  gradeComponents: GradeComponent[]
  grades: Record<number, StudentGrade[]>
  finalGrades: Record<number, FinalGrade>
}>()

// State
const showComponentDialog = ref(false)
const showItemDialog = ref(false)
const showDeleteDialog = ref(false)
const editingComponent = ref<GradeComponent | null>(null)
const editingItem = ref<{ item: GradeItem; componentId: number } | null>(null)
const deletingItem = ref<{ type: 'component' | 'item'; id: number } | null>(null)
const activeTab = ref('overview')
const selectedComponentForItem = ref<number | null>(null)

// Component Form
const componentForm = ref({
  name: '',
  weight: 0,
  description: '',
  order: 0,
})

// Item Form
const itemForm = ref({
  name: '',
  max_points: 0,
  date: '',
  description: '',
  order: 0,
})

// Grade editing
const gradeEdits = ref<Record<string, { points: number | null; remarks: string | null }>>({})
const savingGrades = ref(false)

// Computed
const totalWeight = computed(() => {
  return props.gradeComponents.reduce((sum, c) => sum + parseFloat(c.weight.toString()), 0)
})

const isWeightValid = computed(() => {
  return Math.abs(totalWeight.value - 100) < 0.01
})

const sortedStudents = computed(() => {
  return [...props.students].sort((a, b) => {
    const lastNameCompare = a.last_name.localeCompare(b.last_name)
    if (lastNameCompare !== 0) return lastNameCompare
    return a.first_name.localeCompare(b.first_name)
  })
})

// Format student name
const formatStudentName = (student: Student) => {
  const parts = [student.last_name, student.first_name]
  if (student.suffix) parts.push(student.suffix)
  if (student.middle_name) parts.push(student.middle_name.charAt(0) + '.')
  return parts.join(', ')
}

// Component Management
const openComponentDialog = (component?: GradeComponent) => {
  if (component) {
    editingComponent.value = component
    componentForm.value = {
      name: component.name,
      weight: component.weight,
      description: component.description || '',
      order: component.order,
    }
  } else {
    editingComponent.value = null
    componentForm.value = {
      name: '',
      weight: 0,
      description: '',
      order: props.gradeComponents.length,
    }
  }
  showComponentDialog.value = true
}

const saveComponent = () => {
  if (!componentForm.value.name || componentForm.value.weight <= 0) {
    toast.error('Please fill in all required fields')
    return
  }

  const url = editingComponent.value
    ? route('instructor.class-record.components.update', editingComponent.value.id)
    : route('instructor.class-record.components.store', props.classlist.id)

  const method = editingComponent.value ? 'put' : 'post'

  router[method](url, componentForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(editingComponent.value ? 'Component updated' : 'Component created')
      showComponentDialog.value = false
      editingComponent.value = null
    },
    onError: () => {
      toast.error('Failed to save component')
    },
  })
}

// Item Management
const openItemDialog = (componentId: number, item?: GradeItem) => {
  selectedComponentForItem.value = componentId

  if (item) {
    editingItem.value = { item, componentId }
    itemForm.value = {
      name: item.name,
      max_points: item.max_points,
      date: item.date || '',
      description: item.description || '',
      order: item.order,
    }
  } else {
    editingItem.value = null
    const component = props.gradeComponents.find(c => c.id === componentId)
    itemForm.value = {
      name: '',
      max_points: 0,
      date: '',
      description: '',
      order: component?.grade_items.length || 0,
    }
  }
  showItemDialog.value = true
}

const saveItem = () => {
  if (!itemForm.value.name || itemForm.value.max_points <= 0) {
    toast.error('Please fill in all required fields')
    return
  }

  const url = editingItem.value
    ? route('instructor.class-record.items.update', editingItem.value.item.id)
    : route('instructor.class-record.items.store', selectedComponentForItem.value)

  const method = editingItem.value ? 'put' : 'post'

  router[method](url, itemForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(editingItem.value ? 'Item updated' : 'Item created')
      showItemDialog.value = false
      editingItem.value = null
    },
    onError: () => {
      toast.error('Failed to save item')
    },
  })
}

// Delete
const confirmDelete = (type: 'component' | 'item', id: number) => {
  deletingItem.value = { type, id }
  showDeleteDialog.value = true
}

const deleteItem = () => {
  if (!deletingItem.value) return

  const url = deletingItem.value.type === 'component'
    ? route('instructor.class-record.components.destroy', deletingItem.value.id)
    : route('instructor.class-record.items.destroy', deletingItem.value.id)

  router.delete(url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`${deletingItem.value!.type === 'component' ? 'Component' : 'Item'} deleted`)
      showDeleteDialog.value = false
      deletingItem.value = null
    },
    onError: () => {
      toast.error('Failed to delete')
    },
  })
}

// Grade Management
const getGradeKey = (itemId: number, userId: number) => `${itemId}-${userId}`

const getStudentGrade = (itemId: number, userId: number): StudentGrade | null => {
  const studentGrades = props.grades[userId] || []
  return studentGrades.find(g => g.grade_item_id === itemId) || null
}

const initializeGradeEdits = (item: GradeItem) => {
  sortedStudents.value.forEach(student => {
    const key = getGradeKey(item.id, student.id)
    if (!gradeEdits.value[key]) {
      const existingGrade = getStudentGrade(item.id, student.id)
      gradeEdits.value[key] = {
        points: existingGrade?.points ?? null,
        remarks: existingGrade?.remarks ?? null,
      }
    }
  })
}

const saveGrades = (item: GradeItem) => {
  savingGrades.value = true

  const grades = sortedStudents.value.map(student => {
    const key = getGradeKey(item.id, student.id)
    const edit = gradeEdits.value[key]
    return {
      user_id: student.id,
      points: edit?.points,
      remarks: edit?.remarks,
    }
  })

  router.post(route('instructor.class-record.grades.update', item.id), { grades }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Grades saved successfully')
      savingGrades.value = false
    },
    onError: () => {
      toast.error('Failed to save grades')
      savingGrades.value = false
    },
  })
}

// Export
const exportToCSV = () => {
  window.location.href = route('instructor.class-record.export', props.classlist.id)
}

// Get student final grade
const getStudentFinalGrade = (studentId: number): FinalGrade => {
  return props.finalGrades[studentId] || {
    final_grade: 0,
    total_weighted_score: 0,
    letter_grade: 'N/A',
    breakdown: [],
  }
}

// Get letter grade color
const getLetterGradeColor = (grade: string) => {
  if (grade.startsWith('A')) return 'bg-green-100 text-green-800'
  if (grade.startsWith('B')) return 'bg-blue-100 text-blue-800'
  if (grade.startsWith('C')) return 'bg-yellow-100 text-yellow-800'
  if (grade.startsWith('D')) return 'bg-orange-100 text-orange-800'
  return 'bg-red-100 text-red-800'
}

// Get percentage color
const getPercentageColor = (percentage: number) => {
  if (percentage >= 90) return 'text-green-600'
  if (percentage >= 80) return 'text-blue-600'
  if (percentage >= 70) return 'text-yellow-600'
  if (percentage >= 60) return 'text-orange-600'
  return 'text-red-600'
}
</script>

<template>
  <AppLayout>
    <Head :title="`Class Record - ${classlist.name}`" />

    <div class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Button variant="ghost" size="sm" @click="router.visit(route('instructor.activities.index', classlist.id))" class="mb-4">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Back to Class
        </Button>

        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Class Record</h1>
            <p class="text-muted-foreground mt-1">{{ classlist.name }} • {{ classlist.academic_year }}</p>
            <div class="flex items-center gap-2 mt-2">
              <Badge variant="outline">{{ students.length }} Students</Badge>
              <Badge variant="outline">{{ gradeComponents.length }} Components</Badge>
              <Badge :variant="isWeightValid ? 'default' : 'destructive'">
                Total Weight: {{ totalWeight.toFixed(1) }}%
              </Badge>
            </div>
          </div>

          <div class="flex gap-2">
            <Button @click="openComponentDialog()" variant="default">
              <Plus class="w-4 h-4 mr-2" />
              Add Component
            </Button>
            <Button @click="exportToCSV()" variant="outline">
              <Download class="w-4 h-4 mr-2" />
              Export CSV
            </Button>
          </div>
        </div>
      </div>

      <!-- Weight Warning -->
      <Card v-if="!isWeightValid" class="mb-6 border-orange-500 bg-orange-50">
        <CardContent class="pt-6">
          <div class="flex items-center gap-2 text-orange-800">
            <Trophy class="w-5 h-5" />
            <p class="font-medium">
              Warning: Component weights total {{ totalWeight.toFixed(1) }}% instead of 100%.
              Final grades may not be accurate.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Main Content -->
      <Tabs v-model="activeTab" class="space-y-6">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="grades">Grade Entry</TabsTrigger>
          <TabsTrigger value="final">Final Grades</TabsTrigger>
        </TabsList>

        <!-- Overview Tab -->
        <TabsContent value="overview" class="space-y-6">
          <div v-if="gradeComponents.length === 0" class="text-center py-12">
            <FileSpreadsheet class="w-16 h-16 mx-auto text-muted-foreground mb-4" />
            <h3 class="text-lg font-semibold mb-2">No Grade Components Yet</h3>
            <p class="text-muted-foreground mb-4">Start by creating grade components like Assignments, Quizzes, or Exams</p>
            <Button @click="openComponentDialog()">
              <Plus class="w-4 h-4 mr-2" />
              Create First Component
            </Button>
          </div>

          <!-- Components List -->
          <div v-else class="space-y-4">
            <Card v-for="component in gradeComponents" :key="component.id">
              <CardHeader>
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <GripVertical class="w-5 h-5 text-muted-foreground" />
                      <CardTitle>{{ component.name }}</CardTitle>
                      <Badge variant="secondary">{{ component.weight }}%</Badge>
                    </div>
                    <CardDescription v-if="component.description" class="mt-2">
                      {{ component.description }}
                    </CardDescription>
                  </div>
                  <div class="flex gap-2">
                    <Button @click="openItemDialog(component.id)" size="sm" variant="outline">
                      <Plus class="w-4 h-4 mr-2" />
                      Add Item
                    </Button>
                    <Button @click="openComponentDialog(component)" size="sm" variant="ghost">
                      <Edit class="w-4 h-4" />
                    </Button>
                    <Button @click="confirmDelete('component', component.id)" size="sm" variant="ghost">
                      <Trash2 class="w-4 h-4 text-red-600" />
                    </Button>
                  </div>
                </div>
              </CardHeader>

              <CardContent>
                <div v-if="component.grade_items.length === 0" class="text-center py-8 text-muted-foreground">
                  <p>No items yet. Click "Add Item" to create one.</p>
                </div>

                <div v-else class="space-y-2">
                  <div
                    v-for="item in component.grade_items"
                    :key="item.id"
                    class="flex items-center justify-between p-3 rounded-lg border hover:bg-accent/50 transition-colors"
                  >
                    <div class="flex items-center gap-3 flex-1">
                      <GripVertical class="w-4 h-4 text-muted-foreground" />
                      <div class="flex-1">
                        <div class="font-medium">{{ item.name }}</div>
                        <div class="text-sm text-muted-foreground">
                          {{ item.max_points }} points
                          <span v-if="item.date"> • {{ new Date(item.date).toLocaleDateString() }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="flex gap-2">
                      <Button @click="openItemDialog(component.id, item)" size="sm" variant="ghost">
                        <Edit class="w-4 h-4" />
                      </Button>
                      <Button @click="confirmDelete('item', item.id)" size="sm" variant="ghost">
                        <Trash2 class="w-4 h-4 text-red-600" />
                      </Button>
                    </div>
                  </div>

                  <div class="mt-4 pt-4 border-t flex items-center justify-between text-sm">
                    <span class="font-medium">Total:</span>
                    <span>{{ component.grade_items.reduce((sum, item) => sum + item.max_points, 0) }} points</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Grade Entry Tab -->
        <TabsContent value="grades" class="space-y-6">
          <div v-if="gradeComponents.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">Create grade components first to enter grades.</p>
          </div>

          <div v-else class="space-y-6">
            <Card v-for="component in gradeComponents" :key="component.id">
              <CardHeader>
                <CardTitle>{{ component.name }} ({{ component.weight }}%)</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div v-if="component.grade_items.length === 0" class="text-center py-8 text-muted-foreground">
                  <p>No items to grade. Add items first.</p>
                </div>

                <div v-else v-for="item in component.grade_items" :key="item.id" class="space-y-4">
                  <div class="flex items-center justify-between border-b pb-2">
                    <div>
                      <h4 class="font-semibold">{{ item.name }}</h4>
                      <p class="text-sm text-muted-foreground">Max: {{ item.max_points }} points</p>
                    </div>
                    <Button @click="saveGrades(item)" :disabled="savingGrades" size="sm">
                      <Save class="w-4 h-4 mr-2" />
                      Save Grades
                    </Button>
                  </div>

                  <div class="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead class="w-[300px]">Student</TableHead>
                          <TableHead class="w-[150px]">Points</TableHead>
                          <TableHead>Remarks</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow v-for="student in sortedStudents" :key="student.id">
                          <TableCell class="font-medium">
                            {{ formatStudentName(student) }}
                          </TableCell>
                          <TableCell>
                            <Input
                              v-model.number="gradeEdits[getGradeKey(item.id, student.id)].points"
                              type="number"
                              :min="0"
                              :max="item.max_points"
                              step="0.01"
                              class="w-full"
                              @focus="initializeGradeEdits(item)"
                            />
                          </TableCell>
                          <TableCell>
                            <Input
                              v-model="gradeEdits[getGradeKey(item.id, student.id)].remarks"
                              placeholder="Optional remarks"
                              class="w-full"
                              @focus="initializeGradeEdits(item)"
                            />
                          </TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Final Grades Tab -->
        <TabsContent value="final" class="space-y-6">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Final Grades</CardTitle>
                  <CardDescription>Calculated based on weighted components</CardDescription>
                </div>
                <Button @click="exportToCSV()" variant="outline" size="sm">
                  <Download class="w-4 h-4 mr-2" />
                  Export
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Student</TableHead>
                      <TableHead v-for="component in gradeComponents" :key="component.id">
                        {{ component.name }}
                        <br />
                        <span class="text-xs text-muted-foreground">({{ component.weight }}%)</span>
                      </TableHead>
                      <TableHead class="text-right">Final Grade</TableHead>
                      <TableHead class="text-right">Letter</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="student in sortedStudents" :key="student.id">
                      <TableCell class="font-medium">
                        {{ formatStudentName(student) }}
                      </TableCell>
                      <TableCell v-for="component in gradeComponents" :key="component.id">
                        <div class="text-sm">
                          {{
                            getStudentFinalGrade(student.id).breakdown.find(b => b.component === component.name)?.percentage.toFixed(1) || '0.0'
                          }}%
                        </div>
                      </TableCell>
                      <TableCell class="text-right">
                        <span :class="getPercentageColor(getStudentFinalGrade(student.id).final_grade)" class="font-semibold">
                          {{ getStudentFinalGrade(student.id).final_grade.toFixed(2) }}%
                        </span>
                      </TableCell>
                      <TableCell class="text-right">
                        <Badge :class="getLetterGradeColor(getStudentFinalGrade(student.id).letter_grade)">
                          {{ getStudentFinalGrade(student.id).letter_grade }}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <!-- Component Dialog -->
      <Dialog v-model:open="showComponentDialog">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle>{{ editingComponent ? 'Edit' : 'Add' }} Grade Component</DialogTitle>
            <DialogDescription>
              Define a category like Assignments, Quizzes, or Exams
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4">
            <div>
              <Label for="component-name">Name *</Label>
              <Input
                id="component-name"
                v-model="componentForm.name"
                placeholder="e.g., Assignments, Quizzes, Exams"
              />
            </div>

            <div>
              <Label for="component-weight">Weight (%) *</Label>
              <Input
                id="component-weight"
                v-model.number="componentForm.weight"
                type="number"
                min="0"
                max="100"
                step="0.01"
              />
              <p class="text-xs text-muted-foreground mt-1">
                Current total: {{ totalWeight.toFixed(1) }}%
                {{ !editingComponent ? `+ ${componentForm.weight}% = ${(totalWeight + componentForm.weight).toFixed(1)}%` : '' }}
              </p>
            </div>

            <div>
              <Label for="component-description">Description</Label>
              <Textarea
                id="component-description"
                v-model="componentForm.description"
                placeholder="Optional description"
                rows="3"
              />
            </div>
          </div>

          <DialogFooter>
            <Button @click="showComponentDialog = false" variant="outline">Cancel</Button>
            <Button @click="saveComponent()">
              <Save class="w-4 h-4 mr-2" />
              Save
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Item Dialog -->
      <Dialog v-model:open="showItemDialog">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle>{{ editingItem ? 'Edit' : 'Add' }} Grade Item</DialogTitle>
            <DialogDescription>
              Add a specific graded item like Quiz 1 or Assignment 2
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4">
            <div>
              <Label for="item-name">Name *</Label>
              <Input
                id="item-name"
                v-model="itemForm.name"
                placeholder="e.g., Quiz 1, Midterm Exam"
              />
            </div>

            <div>
              <Label for="item-points">Maximum Points *</Label>
              <Input
                id="item-points"
                v-model.number="itemForm.max_points"
                type="number"
                min="0"
                step="0.01"
              />
            </div>

            <div>
              <Label for="item-date">Date</Label>
              <Input
                id="item-date"
                v-model="itemForm.date"
                type="date"
              />
            </div>

            <div>
              <Label for="item-description">Description</Label>
              <Textarea
                id="item-description"
                v-model="itemForm.description"
                placeholder="Optional description"
                rows="3"
              />
            </div>
          </div>

          <DialogFooter>
            <Button @click="showItemDialog = false" variant="outline">Cancel</Button>
            <Button @click="saveItem()">
              <Save class="w-4 h-4 mr-2" />
              Save
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Delete Confirmation Dialog -->
      <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirm Deletion</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete this {{ deletingItem?.type }}?
              {{ deletingItem?.type === 'component' ? 'All items and grades within it will also be deleted.' : 'All student grades for this item will be deleted.' }}
              This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button @click="showDeleteDialog = false" variant="outline">Cancel</Button>
            <Button @click="deleteItem()" variant="destructive">
              <Trash2 class="w-4 h-4 mr-2" />
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
