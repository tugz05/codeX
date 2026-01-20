<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ArrowLeft, Calendar, Clock, CheckCircle2, XCircle, AlertCircle, UserCheck, Plus, Edit, Trash2, FileText } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = withDefaults(defineProps<{
  classlist: {
    id: string
    name: string
    academic_year: string
    room: string
  }
  sessions: Array<{
    id: number
    session_date: string
    session_time: string | null
    notes: string | null
    total_students: number
    present_count: number
    absent_count: number
    late_count: number
    excused_count: number
  }>
  students: Array<{
    id: number
    name: string
    email: string
  }>
  studentStats: Array<{
    student: {
      id: number
      name: string
      email: string
    }
    total_sessions: number
    present: number
    absent: number
    late: number
    excused: number
    attendance_percentage: number
  }>
  showBack?: boolean
}>(), {
  showBack: true,
})

const showMarkDialog = ref(false)
const showEditDialog = ref(false)
const selectedSession = ref<any>(null)
const editingSessionId = ref<number | null>(null)
const hasStudents = computed(() => props.students.length > 0)

const form = useForm({
  session_date: new Date().toISOString().split('T')[0],
  session_time: new Date().toTimeString().slice(0, 5),
  notes: '',
  records: [] as Array<{
    user_id: number
    status: string
    notes?: string
  }>,
})

const editForm = useForm({
  session_date: '',
  session_time: '',
  notes: '',
  records: [] as Array<{
    id?: number
    user_id: number
    status: string
    notes?: string
  }>,
})

function setAllStatuses(target: 'present' | 'absent' | 'late' | 'excused', formTarget: 'create' | 'edit') {
  const records = formTarget === 'create' ? form.records : editForm.records
  records.forEach(record => {
    record.status = target
  })
}

function openMarkDialog() {
  form.reset()
  form.session_date = new Date().toISOString().split('T')[0]
  form.session_time = new Date().toTimeString().slice(0, 5)
  form.records = props.students.map(student => ({
    user_id: student.id,
    status: 'present',
  }))
  showMarkDialog.value = true
}

function openEditDialog(session: any) {
  editingSessionId.value = session.id
  selectedSession.value = session

  fetch(route('instructor.attendance.edit', session.id), {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })
    .then(res => {
      if (!res.ok) throw new Error('Failed to fetch')
      return res.json()
    })
    .then(data => {
      editForm.session_date = data.session_date
      editForm.session_time = data.session_time || ''
      editForm.notes = data.notes || ''

      const existingRecords = data.records || []
      editForm.records = props.students.map(student => {
        const existing = existingRecords.find((r: any) => r.user_id === student.id)
        return existing || {
          user_id: student.id,
          status: 'present',
        }
      })

      showEditDialog.value = true
    })
    .catch(() => {
      toast.error('Failed to load session details')
    })
}

function submitMarkAttendance() {
  if (!hasStudents.value) {
    toast.error('You cannot mark attendance because no students are enrolled in this class.')
    return
  }

  form.post(route('instructor.attendance.store', props.classlist.id), {
    onSuccess: () => {
      toast.success('Attendance marked successfully')
      showMarkDialog.value = false
    },
    onError: () => {
      toast.error('Failed to mark attendance')
    },
  })
}

function submitEditAttendance() {
  if (!editingSessionId.value) return

  editForm.put(route('instructor.attendance.update', editingSessionId.value), {
    onSuccess: () => {
      toast.success('Attendance updated successfully')
      showEditDialog.value = false
      editingSessionId.value = null
    },
    onError: () => {
      toast.error('Failed to update attendance')
    },
  })
}

function deleteSession(sessionId: number) {
  if (!confirm('Are you sure you want to delete this attendance session?')) return

  router.delete(route('instructor.attendance.destroy', sessionId), {
    onSuccess: () => {
      toast.success('Attendance session deleted')
    },
    onError: () => {
      toast.error('Failed to delete session')
    },
  })
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'present':
      return CheckCircle2
    case 'absent':
      return XCircle
    case 'late':
      return AlertCircle
    case 'excused':
      return UserCheck
    default:
      return Clock
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'present':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'absent':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    case 'late':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    case 'excused':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
}
</script>

<template>
  <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <Link v-if="showBack" :href="route('instructor.classlist')" as="button">
        <Button variant="outline" size="sm">
          <ArrowLeft class="mr-2 h-4 w-4" /> Back
        </Button>
      </Link>
      <div class="flex-1">
        <h1 class="text-3xl font-bold tracking-tight">Attendance</h1>
        <p class="text-muted-foreground mt-1.5">{{ props.classlist.name }}</p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" as-child>
          <Link :href="route('instructor.attendance.report', props.classlist.id)">
            <FileText class="mr-2 h-4 w-4" /> View Report
          </Link>
        </Button>
        <Button @click="openMarkDialog" size="lg">
          <Plus class="mr-2 h-5 w-5" /> Mark Attendance
        </Button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Sessions</CardTitle>
          <Calendar class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.sessions.length }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Average Attendance</CardTitle>
          <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ props.studentStats.length > 0
              ? Math.round(props.studentStats.reduce((sum, stat) => sum + stat.attendance_percentage, 0) / props.studentStats.length)
              : 0 }}%
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Students</CardTitle>
          <UserCheck class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.students.length }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">This Week</CardTitle>
          <Clock class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ props.sessions.filter(s => {
              const sessionDate = new Date(s.session_date)
              const weekAgo = new Date()
              weekAgo.setDate(weekAgo.getDate() - 7)
              return sessionDate >= weekAgo
            }).length }}
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Sessions List -->
    <Card>
      <CardHeader>
        <CardTitle>Attendance Sessions</CardTitle>
        <CardDescription>View and manage attendance records</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="props.sessions.length > 0" class="space-y-4">
          <div
            v-for="session in props.sessions"
            :key="session.id"
            class="border rounded-lg p-4 hover:bg-muted/50 transition-colors"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <Calendar class="h-4 w-4 text-muted-foreground" />
                  <span class="font-semibold">{{ new Date(session.session_date).toLocaleDateString() }}</span>
                  <span v-if="session.session_time" class="text-sm text-muted-foreground">
                    at {{ session.session_time }}
                  </span>
                </div>
                <div v-if="session.notes" class="text-sm text-muted-foreground mb-2">
                  {{ session.notes }}
                </div>
                <div class="flex gap-4 text-sm">
                  <Badge :class="getStatusColor('present')" class="flex items-center gap-1">
                    <CheckCircle2 class="h-3 w-3" />
                    {{ session.present_count }} Present
                  </Badge>
                  <Badge :class="getStatusColor('absent')" class="flex items-center gap-1">
                    <XCircle class="h-3 w-3" />
                    {{ session.absent_count }} Absent
                  </Badge>
                  <Badge v-if="session.late_count > 0" :class="getStatusColor('late')" class="flex items-center gap-1">
                    <AlertCircle class="h-3 w-3" />
                    {{ session.late_count }} Late
                  </Badge>
                  <Badge v-if="session.excused_count > 0" :class="getStatusColor('excused')" class="flex items-center gap-1">
                    <UserCheck class="h-3 w-3" />
                    {{ session.excused_count }} Excused
                  </Badge>
                </div>
              </div>
              <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="openEditDialog(session)">
                  <Edit class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="sm" @click="deleteSession(session.id)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-12 text-muted-foreground">
          <Calendar class="h-12 w-12 mx-auto mb-4 opacity-50" />
          <p>No attendance sessions yet. Mark attendance to get started.</p>
        </div>
      </CardContent>
    </Card>

    <!-- Student Statistics -->
    <Card>
      <CardHeader>
        <CardTitle>Student Attendance Summary</CardTitle>
        <CardDescription>Overall attendance statistics per student</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Total Sessions</TableHead>
                <TableHead>Present</TableHead>
                <TableHead>Absent</TableHead>
                <TableHead>Late</TableHead>
                <TableHead>Excused</TableHead>
                <TableHead>Attendance %</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="stat in props.studentStats" :key="stat.student.id">
                <TableCell class="font-medium">{{ stat.student.name }}</TableCell>
                <TableCell>{{ stat.total_sessions }}</TableCell>
                <TableCell>
                  <Badge :class="getStatusColor('present')">{{ stat.present }}</Badge>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor('absent')">{{ stat.absent }}</Badge>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor('late')">{{ stat.late }}</Badge>
                </TableCell>
                <TableCell>
                  <Badge :class="getStatusColor('excused')">{{ stat.excused }}</Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="stat.attendance_percentage >= 80 ? 'default' : 'destructive'">
                    {{ stat.attendance_percentage }}%
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>

  <!-- Mark Attendance Dialog -->
  <Dialog v-model:open="showMarkDialog">
    <DialogContent class="!w-[96vw] !max-w-[1400px] !max-h-[96vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Mark Attendance</DialogTitle>
        <DialogDescription>Record attendance for {{ new Date(form.session_date).toLocaleDateString() }}</DialogDescription>
      </DialogHeader>
      <form @submit.prevent="submitMarkAttendance" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="session_date">Date</Label>
            <Input id="session_date" type="date" v-model="form.session_date" required />
          </div>
          <div class="space-y-2">
            <Label for="session_time">Time</Label>
            <Input id="session_time" type="time" v-model="form.session_time" />
          </div>
        </div>
        <div class="space-y-2">
          <Label for="notes">Notes (Optional)</Label>
          <Textarea id="notes" v-model="form.notes" placeholder="Add any notes about this session..." />
        </div>
        <div class="space-y-4">
          <div class="flex items-center justify-between gap-2">
            <Label>Student Attendance</Label>
            <div class="flex items-center gap-2">
              <Button type="button" variant="outline" size="sm" @click="setAllStatuses('present', 'create')">
                Mark All Present
              </Button>
              <Button type="button" variant="outline" size="sm" @click="setAllStatuses('absent', 'create')">
                Mark All Absent
              </Button>
            </div>
          </div>
          <div v-if="hasStudents" class="max-h-[50vh] overflow-y-auto rounded-lg border">
            <Table class="w-full">
              <TableHeader class="sticky top-0 bg-background z-10">
                <TableRow>
                  <TableHead class="w-12">#</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead class="text-right">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(record, index) in form.records" :key="index">
                  <TableCell class="font-medium">{{ index + 1 }}</TableCell>
                  <TableCell class="font-medium">
                    {{ props.students.find(s => s.id === record.user_id)?.name }}
                  </TableCell>
                  <TableCell class="text-muted-foreground">
                    {{ props.students.find(s => s.id === record.user_id)?.email }}
                  </TableCell>
                  <TableCell class="text-right">
                    <Select v-model="record.status">
                      <SelectTrigger class="w-32 ml-auto">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="present">Present</SelectItem>
                        <SelectItem value="absent">Absent</SelectItem>
                        <SelectItem value="late">Late</SelectItem>
                        <SelectItem value="excused">Excused</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div v-else class="text-sm text-muted-foreground border rounded-lg p-4 bg-muted/30">
            No students are currently enrolled in this class. Once students join, you'll be able to mark their attendance here.
          </div>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="showMarkDialog = false">Cancel</Button>
          <Button type="submit" :disabled="form.processing || !hasStudents">
            {{ form.processing ? 'Saving...' : 'Mark Attendance' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>

  <!-- Edit Attendance Dialog -->
  <Dialog v-model:open="showEditDialog">
    <DialogContent class="!w-[96vw] !max-w-[1400px] !max-h-[96vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Edit Attendance</DialogTitle>
        <DialogDescription>Update attendance records for this session</DialogDescription>
      </DialogHeader>
      <form @submit.prevent="submitEditAttendance" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="edit_session_date">Date</Label>
            <Input id="edit_session_date" type="date" v-model="editForm.session_date" required />
          </div>
          <div class="space-y-2">
            <Label for="edit_session_time">Time</Label>
            <Input id="edit_session_time" type="time" v-model="editForm.session_time" />
          </div>
        </div>
        <div class="space-y-2">
          <Label for="edit_notes">Notes (Optional)</Label>
          <Textarea id="edit_notes" v-model="editForm.notes" placeholder="Add any notes about this session..." />
        </div>
        <div class="space-y-4">
          <div class="flex items-center justify-between gap-2">
            <Label>Student Attendance</Label>
            <div class="flex items-center gap-2">
              <Button type="button" variant="outline" size="sm" @click="setAllStatuses('present', 'edit')">
                Mark All Present
              </Button>
              <Button type="button" variant="outline" size="sm" @click="setAllStatuses('absent', 'edit')">
                Mark All Absent
              </Button>
            </div>
          </div>
          <div class="max-h-[50vh] overflow-y-auto rounded-lg border">
            <Table class="w-full">
              <TableHeader class="sticky top-0 bg-background z-10">
                <TableRow>
                  <TableHead class="w-12">#</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead class="text-right">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(record, index) in editForm.records" :key="index">
                  <TableCell class="font-medium">{{ index + 1 }}</TableCell>
                  <TableCell class="font-medium">
                    {{ props.students.find(s => s.id === record.user_id)?.name }}
                  </TableCell>
                  <TableCell class="text-muted-foreground">
                    {{ props.students.find(s => s.id === record.user_id)?.email }}
                  </TableCell>
                  <TableCell class="text-right">
                    <Select v-model="record.status">
                      <SelectTrigger class="w-32 ml-auto">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="present">Present</SelectItem>
                        <SelectItem value="absent">Absent</SelectItem>
                        <SelectItem value="late">Late</SelectItem>
                        <SelectItem value="excused">Excused</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="showEditDialog = false">Cancel</Button>
          <Button type="submit" :disabled="editForm.processing">
            {{ editForm.processing ? 'Updating...' : 'Update Attendance' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
