<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
} from '@/components/ui/alert-dialog'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import VClassCardStudent from '@/components/VClassCardStudent.vue'
import { toast } from 'vue-sonner'
import { Search, BookOpen, Plus } from 'lucide-vue-next'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AuthLayoutStudent.vue'

type CardAction = 'archive' | 'unenroll' | 'edit' | 'delete' | 'copy-link'

interface CardOption {
  label: string
  action: CardAction
  color?: string
}

const props = defineProps<{
  joinedClasses: Array<any>
  archivedClasses?: Array<any>
  joinCode?: string
  requiresStudentAccount?: boolean
}>()

const showDialog = ref(false)
const classCode = ref('')
const searchQuery = ref('')

// Confirmation dialog state
const confirmDialog = ref(false)
const confirmType = ref<'archive' | 'unenroll' | null>(null)
const targetClassId = ref<string | null>(null)

const form = useForm({
  class_code: ''
})

function openDialog() { showDialog.value = true }
function closeDialog() { 
  showDialog.value = false
  form.reset()
  classCode.value = ''
  // Remove code from URL if it exists
  if (props.joinCode) {
    router.get(route('student.classlist'), {}, { preserveState: true, preserveScroll: true })
  }
}

// Auto-open dialog with code if provided via query parameter
onMounted(() => {
  if (props.joinCode) {
    classCode.value = props.joinCode
    showDialog.value = true
  }
})

function joinClass() {
  form.class_code = classCode.value
  form.post(route('student.class.join'), {
    onSuccess: () => { closeDialog(); toast.success('You have joined the class.') },
    onError: () => toast.error('Failed to join class.')
  })
}

function requestUnenroll(id: string) {
  targetClassId.value = id
  confirmType.value = 'unenroll'
  confirmDialog.value = true
}
function requestArchive(id: string) {
  targetClassId.value = id
  confirmType.value = 'archive'
  confirmDialog.value = true
}
function doConfirm() {
  if (!targetClassId.value) return
  if (confirmType.value === 'unenroll') {
    form.post(route('student.class.unenroll', targetClassId.value), {
      onSuccess: () => {
        toast.success('You have left the class.')
        confirmDialog.value = false
        targetClassId.value = null
      },
      onError: () => toast.error('Failed to leave the class.')
    })
  } else if (confirmType.value === 'archive') {
    form.post(route('student.class.archive', targetClassId.value), {
      onSuccess: () => {
        toast.success('Class archived successfully.')
        confirmDialog.value = false
        targetClassId.value = null
      },
      onError: () => toast.error('Failed to archive class.')
    })
  }
}
function cancelConfirm() {
  confirmDialog.value = false
  targetClassId.value = null
}

const cardMenuOptions: CardOption[] = [
  { label: 'Archive', action: 'archive' },
  { label: 'Unenroll', action: 'unenroll', color: 'text-red-500' }
]

const restoreClass = (id: string) => {
  form.post(route('student.class.restore', id), {
    onSuccess: () => {
      toast.success('Class restored successfully.')
    },
    onError: () => toast.error('Failed to restore class.')
  })
}

const filteredClasses = computed(() => {
  if (!searchQuery.value.trim()) return props.joinedClasses
  const query = searchQuery.value.toLowerCase()
  return props.joinedClasses.filter((cls) =>
    cls.name?.toLowerCase().includes(query) ||
    cls.room?.toLowerCase().includes(query) ||
    cls.section?.toLowerCase().includes(query) ||
    cls.academic_year?.toLowerCase().includes(query)
  )
})
</script>

<template>
  <Head title="My Classes" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Alert for non-student accounts -->
      <Alert v-if="props.requiresStudentAccount" variant="destructive" class="border-2">
        <AlertTitle>Student Account Required</AlertTitle>
        <AlertDescription>
          You are currently logged in as a non-student account. To join classes, please log out and log in with a student account.
          <Button 
            @click="() => window.location.href = route('logout')" 
            variant="outline" 
            size="sm" 
            class="ml-2 mt-2"
          >
            Log Out
          </Button>
        </AlertDescription>
      </Alert>

      <!-- Page Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">My Classes</h1>
          <p class="text-muted-foreground mt-1.5">
            Access your enrolled classes and course materials
          </p>
        </div>
        <Button @click="openDialog" size="lg" class="transition-all duration-200 hover:scale-105">
          <Plus class="mr-2 h-5 w-5" /> Join a Class
        </Button>
      </div>

      <!-- Search Bar -->
      <div v-if="props.joinedClasses.length > 0" class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          placeholder="Search classes by name, room, section, or academic year..."
          class="pl-10 border-2 transition-all duration-300 focus:scale-[1.01]"
        />
      </div>

      <!-- Joined classes list -->
      <div v-if="filteredClasses.length > 0" class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <VClassCardStudent
          v-for="cls in filteredClasses"
          :key="cls.id"
          :id="cls.id"
          :title="cls.name"
          :room="cls.room"
          :section="cls.section || null"
          :academic-year="cls.academic_year"
          :joined-at="cls.joined_at"
          :options="cardMenuOptions"
          :to-url="route('student.activities.index', cls.id)"
          @unenroll="requestUnenroll"
          @archive="requestArchive"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="props.joinedClasses.length === 0" class="flex min-h-[60vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
        <div class="mx-auto max-w-md text-center">
          <BookOpen class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">No classes yet</h3>
          <p class="text-muted-foreground mb-6">
            Join a class using the class code provided by your instructor to get started.
          </p>
          <Button @click="openDialog" size="lg" class="transition-all duration-200 hover:scale-105">
            <Plus class="mr-2 h-5 w-5" /> Join Your First Class
          </Button>
        </div>
      </div>

      <!-- No Search Results -->
      <div v-else class="flex min-h-[40vh] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-muted/30 p-12">
        <div class="mx-auto max-w-md text-center">
          <Search class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">No classes found</h3>
          <p class="text-muted-foreground">
            Try adjusting your search query to find what you're looking for.
          </p>
        </div>
      </div>
    </div>

    <!-- AlertDialog for joining class -->
    <AlertDialog :open="showDialog" @update:open="showDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Join a Class</AlertDialogTitle>
          <AlertDialogDescription>
            <span v-if="props.requiresStudentAccount">
              You need to log in as a student to join this class. Please log out and log in with a student account.
            </span>
            <span v-else>Enter the class code provided by your instructor.</span>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <form @submit.prevent="joinClass" class="space-y-4">
          <Input 
            v-model="classCode" 
            placeholder="Class code" 
            required 
            autofocus 
            class="border-2"
            :disabled="props.requiresStudentAccount"
          />
          <Alert v-if="props.requiresStudentAccount" variant="destructive">
            <AlertTitle>Account Type Required</AlertTitle>
            <AlertDescription>
              Only student accounts can join classes. Please log out and log in with a student account to continue.
            </AlertDescription>
          </Alert>
          <Alert v-else-if="form.errors.class_code" variant="destructive">
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ form.errors.class_code }}</AlertDescription>
          </Alert>
        </form>
        <AlertDialogFooter>
          <AlertDialogCancel @click="closeDialog">Cancel</AlertDialogCancel>
          <AlertDialogAction as-child v-if="!props.requiresStudentAccount">
            <Button @click="joinClass" :disabled="form.processing">
              {{ form.processing ? 'Joining...' : 'Join' }}
            </Button>
          </AlertDialogAction>
          <AlertDialogAction as-child v-else>
            <Button @click="() => window.location.href = route('logout')" variant="destructive">
              Log Out
            </Button>
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Confirmation Dialog for Unenroll/Archive -->
    <AlertDialog :open="confirmDialog" @update:open="confirmDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>
            {{ confirmType === 'unenroll' ? 'Leave Class' : 'Archive Class' }}
          </AlertDialogTitle>
          <AlertDialogDescription>
            {{ confirmType === 'unenroll'
              ? 'Are you sure you want to leave this class? You will lose access to all class materials and assignments.'
              : 'Are you sure you want to archive this class? You can restore it later from the archived section.' }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="cancelConfirm">Cancel</AlertDialogCancel>
          <AlertDialogAction as-child>
            <Button
              :variant="confirmType === 'unenroll' ? 'destructive' : 'default'"
              @click="doConfirm"
            >
              {{ confirmType === 'unenroll' ? 'Yes, Leave' : 'Yes, Archive' }}
            </Button>
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
