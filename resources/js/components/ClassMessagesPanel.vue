<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { SendHorizontal, User } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { echo } from '@/lib/echo'

type MessageUser = { id: number; name: string; avatar?: string | null }
type MessageItem = {
  id: number
  body: string
  sender_id: number
  recipient_id: number
  created_at: string
  sender?: MessageUser | null
  recipient?: MessageUser | null
}
type StudentItem = { id: number; name: string; email: string; avatar?: string | null; unread_count?: number }

const props = defineProps<{
  classlistId: string
  mode: 'student' | 'instructor'
}>()

const page = usePage()
const currentUserId = computed(() => page.props.auth?.user?.id as number | undefined)

const loading = ref(false)
const sending = ref(false)
const messages = ref<MessageItem[]>([])
const messageText = ref('')
const instructor = ref<MessageUser | null>(null)
const students = ref<StudentItem[]>([])
const selectedStudentId = ref<number | null>(null)
const studentQuery = ref('')
const isTyping = ref(false)
const typingName = ref<string | null>(null)
const messagesContainer = ref<HTMLDivElement | null>(null)
const unreadCount = ref(0)
let channelName: string | null = null
let typingTimeout: number | null = null
let typingEmitTimeout: number | null = null

const filteredStudents = computed(() => {
  if (!studentQuery.value) return students.value
  const q = studentQuery.value.toLowerCase()
  return students.value.filter(student =>
    `${student.name} ${student.email}`.toLowerCase().includes(q)
  )
})

const selectedStudent = computed(() =>
  students.value.find(student => student.id === selectedStudentId.value) ?? null
)

const totalUnread = computed(() =>
  students.value.reduce((sum, student) => sum + (student.unread_count ?? 0), 0)
)

const canSend = computed(() => {
  if (sending.value) return false
  if (!messageText.value.trim()) return false
  if (props.mode === 'instructor' && !selectedStudentId.value) return false
  if (props.mode === 'student' && !instructor.value) return false
  return true
})

const messageEndpoint = (studentId?: number | null) => {
  if (props.mode === 'student') {
    return route('student.messages.index', props.classlistId)
  }
  const base = route('instructor.messages.index', props.classlistId)
  return studentId ? `${base}?student_id=${studentId}` : base
}

const messageStoreEndpoint = (studentId?: number | null) => {
  if (props.mode === 'student') {
    return route('student.messages.store', props.classlistId)
  }
  return route('instructor.messages.store.student', [props.classlistId, studentId ?? 0])
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const loadMessages = async (studentId?: number | null) => {
  if (loading.value) return
  loading.value = true
  try {
    const response = await fetch(messageEndpoint(studentId), {
      headers: {
        Accept: 'application/json',
      },
    })
    if (!response.ok) throw new Error('Failed to load messages.')
    const data = await response.json()

    if (props.mode === 'instructor') {
      students.value = data.students ?? []
      selectedStudentId.value = data.selected_student_id ?? selectedStudentId.value
    } else {
      instructor.value = data.instructor ?? null
      unreadCount.value = Number(data.unread_count ?? 0)
    }

    messages.value = (data.messages ?? []) as MessageItem[]
    if (props.mode === 'instructor' && selectedStudentId.value) {
      const selected = students.value.find(student => student.id === selectedStudentId.value)
      if (selected) selected.unread_count = 0
    }
    await scrollToBottom()
  } catch (error) {
    toast.error('Unable to load messages.')
  } finally {
    loading.value = false
  }
}

const sendMessage = async () => {
  if (!canSend.value) return
  sending.value = true

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  try {
    const response = await fetch(messageStoreEndpoint(selectedStudentId.value), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        body: messageText.value.trim(),
      }),
    })
    if (!response.ok) throw new Error('Failed to send message.')
    const data = await response.json()
    if (data.message && !messages.value.find(message => message.id === data.message.id)) {
      messages.value.push(data.message)
      await scrollToBottom()
    }
    messageText.value = ''
  } catch (error) {
    toast.error('Message failed to send.')
  } finally {
    sending.value = false
  }
}

const handleTypingInput = () => {
  if (typingEmitTimeout) window.clearTimeout(typingEmitTimeout)
  typingEmitTimeout = window.setTimeout(emitTyping, 250)
}

const emitTyping = () => {
  if (!echo || !channelName || !currentUserId.value) return
  if (props.mode === 'instructor' && !selectedStudentId.value) return

  const payload: Record<string, any> = {
    user_id: currentUserId.value,
    user_name: page.props.auth?.user?.name ?? 'Someone',
  }

  if (props.mode === 'instructor') {
    payload.student_id = selectedStudentId.value
  } else if (instructor.value) {
    payload.instructor_id = instructor.value.id
  }

  echo.private(channelName).whisper('typing', payload)
}

const handleTypingWhisper = (payload: any) => {
  if (!payload || payload.user_id === currentUserId.value) return

  if (props.mode === 'instructor') {
    if (!selectedStudentId.value || payload.student_id !== selectedStudentId.value) return
  } else if (props.mode === 'student') {
    if (instructor.value && payload.instructor_id !== instructor.value.id) return
  }

  isTyping.value = true
  typingName.value = payload.user_name ?? 'Someone'

  if (typingTimeout) window.clearTimeout(typingTimeout)
  typingTimeout = window.setTimeout(() => {
    isTyping.value = false
    typingName.value = null
  }, 1500)
}

const setupRealtime = () => {
  if (!echo || !currentUserId.value) return
  channelName = `classlist.${props.classlistId}.user.${currentUserId.value}`
  echo
    .private(channelName)
    .listen('.class-message.sent', async (payload: any) => {
    if (payload?.classlist_id !== props.classlistId) return

    if (props.mode === 'instructor' && selectedStudentId.value) {
      if (payload.student_id !== selectedStudentId.value) return
    }

    const incoming = payload.message as MessageItem | undefined
    if (!incoming || messages.value.find(message => message.id === incoming.id)) return

    if (props.mode === 'instructor' && payload.student_id) {
      const student = students.value.find(item => item.id === payload.student_id)
      if (student) {
        if (payload.student_id !== selectedStudentId.value) {
          student.unread_count = (student.unread_count ?? 0) + 1
        } else {
          student.unread_count = 0
        }
      }
    }
    if (props.mode === 'student') {
      unreadCount.value = 0
    }

    messages.value.push(incoming)
    await scrollToBottom()
    })
    .listenForWhisper('typing', handleTypingWhisper)
}

const teardownRealtime = () => {
  if (!echo || !channelName || !currentUserId.value) return
  echo.leave(channelName)
  channelName = null
}

watch(
  () => selectedStudentId.value,
  async (studentId, previous) => {
    if (props.mode !== 'instructor') return
    if (loading.value) return
    if (studentId && studentId !== previous) {
      await loadMessages(studentId)
    }
  }
)

onMounted(async () => {
  await loadMessages()
  setupRealtime()
})

onBeforeUnmount(() => {
  teardownRealtime()
})
</script>

<template>
  <Card class="border-2 shadow-sm">
    <CardHeader class="pb-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <CardTitle class="text-base font-semibold">Class Messages</CardTitle>
      <p class="text-sm text-muted-foreground">
        <span v-if="mode === 'student'">
          Message your instructor for this subject in real time.
        </span>
        <span v-else>
          Reply to students enrolled in this class.
        </span>
      </p>
        </div>
        <div class="flex items-center gap-2">
          <span class="rounded-full border px-2.5 py-1 text-xs text-muted-foreground">
            Live
          </span>
          <span
            v-if="mode === 'student' && unreadCount > 0"
            class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-semibold text-white"
          >
            {{ unreadCount }} unread
          </span>
          <span
            v-else-if="mode === 'instructor' && totalUnread > 0"
            class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-semibold text-white"
          >
            {{ totalUnread }} unread
          </span>
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <div
        v-if="mode === 'instructor'"
        class="grid gap-4 md:grid-cols-[260px_1fr]"
      >
        <div class="hidden rounded-lg border bg-card p-3 shadow-sm md:sticky md:top-4 md:block">
          <div class="mb-3">
            <Input v-model="studentQuery" placeholder="Search students..." />
          </div>
          <div class="max-h-[40vh] space-y-1 overflow-y-auto md:max-h-[55vh]">
            <button
              v-for="student in filteredStudents"
              :key="student.id"
              type="button"
              class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-muted/40"
              :class="student.id === selectedStudentId ? 'bg-muted/50 font-semibold ring-1 ring-primary/30' : ''"
              @click="selectedStudentId = student.id"
            >
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                <img v-if="student.avatar" :src="student.avatar" class="h-8 w-8 rounded-full object-cover" />
                <User v-else class="h-4 w-4 text-muted-foreground" />
              </div>
              <div class="min-w-0">
                <p class="truncate">{{ student.name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ student.email }}</p>
              </div>
              <span
                v-if="student.unread_count"
                class="ml-auto rounded-full bg-rose-500 px-2 py-0.5 text-xs font-semibold text-white"
              >
                {{ student.unread_count }}
              </span>
            </button>
            <div v-if="filteredStudents.length === 0" class="text-xs text-muted-foreground">
              No students found.
            </div>
          </div>
        </div>

        <div class="flex flex-col rounded-lg border bg-card shadow-sm md:min-h-[60vh]">
          <div class="border-b px-4 py-3 text-sm font-medium flex flex-wrap items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-xs font-semibold">
              <img v-if="selectedStudent?.avatar" :src="selectedStudent.avatar" class="h-9 w-9 rounded-full object-cover" />
              <User v-else class="h-4 w-4 text-muted-foreground" />
            </div>
            <div>
              <p v-if="selectedStudent" class="font-medium">Conversation with {{ selectedStudent.name }}</p>
              <p v-else class="text-muted-foreground">Select a student to view messages.</p>
              <p v-if="selectedStudent" class="text-xs text-muted-foreground">Student</p>
            </div>
            <div class="w-full md:ml-auto md:w-auto">
              <Select
                v-if="students.length"
                :model-value="selectedStudentId ? String(selectedStudentId) : ''"
                @update:model-value="value => (selectedStudentId = value ? Number(value) : null)"
              >
                <SelectTrigger class="w-full md:w-[220px]">
                  <SelectValue placeholder="Choose student" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="student in students"
                    :key="student.id"
                    :value="String(student.id)"
                  >
                    {{ student.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div
            ref="messagesContainer"
            class="flex-1 space-y-3 overflow-y-auto px-4 py-4 bg-muted/10"
            style="min-height: 260px; max-height: 55vh;"
          >
            <div v-if="loading" class="text-sm text-muted-foreground">Loading messages...</div>
            <div v-else-if="messages.length === 0" class="text-sm text-muted-foreground">
              No messages yet.
            </div>
            <div v-for="message in messages" :key="message.id" class="flex flex-col gap-1">
              <div
                class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow-sm"
                :class="message.sender_id === currentUserId ? 'self-end bg-primary text-primary-foreground' : 'self-start bg-white text-foreground border'"
              >
                <p class="whitespace-pre-wrap">{{ message.body }}</p>
              </div>
              <span class="text-[11px] text-muted-foreground" :class="message.sender_id === currentUserId ? 'self-end' : 'self-start'">
                {{ new Date(message.created_at).toLocaleString() }}
              </span>
            </div>
            <div v-if="isTyping && typingName" class="text-xs text-muted-foreground">
              {{ typingName }} is typing...
            </div>
          </div>
          <div class="border-t px-4 py-3 bg-card sticky bottom-0">
            <div class="flex items-end gap-2">
              <Textarea
                v-model="messageText"
                placeholder="Type your message..."
                rows="1"
                class="h-10 min-h-10 resize-none"
                @input="handleTypingInput"
                @keydown.enter.exact.prevent="sendMessage"
              />
              <Button :disabled="!canSend" class="h-10 px-4" @click="sendMessage">
                <SendHorizontal class="mr-2 h-4 w-4" /> Send
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col rounded-lg border bg-card shadow-sm md:min-h-[60vh]">
        <div class="border-b px-4 py-3 text-sm font-medium flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-xs font-semibold">
            <img v-if="instructor?.avatar" :src="instructor.avatar" class="h-9 w-9 rounded-full object-cover" />
            <User v-else class="h-4 w-4 text-muted-foreground" />
          </div>
          <div>
            <p v-if="instructor" class="font-medium">Conversation with {{ instructor.name }}</p>
            <p v-else class="text-muted-foreground">Instructor not available.</p>
            <p v-if="instructor" class="text-xs text-muted-foreground">Instructor</p>
          </div>
        </div>
        <div
          ref="messagesContainer"
          class="flex-1 space-y-3 overflow-y-auto px-4 py-4 bg-muted/10"
          style="min-height: 260px; max-height: 60vh;"
        >
          <div v-if="loading" class="text-sm text-muted-foreground">Loading messages...</div>
          <div v-else-if="messages.length === 0" class="text-sm text-muted-foreground">
            No messages yet. Start the conversation!
          </div>
          <div v-for="message in messages" :key="message.id" class="flex flex-col gap-1">
            <div
              class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow-sm"
              :class="message.sender_id === currentUserId ? 'self-end bg-primary text-primary-foreground' : 'self-start bg-white text-foreground border'"
            >
              <p class="whitespace-pre-wrap">{{ message.body }}</p>
            </div>
            <span class="text-[11px] text-muted-foreground" :class="message.sender_id === currentUserId ? 'self-end' : 'self-start'">
              {{ new Date(message.created_at).toLocaleString() }}
            </span>
          </div>
          <div v-if="isTyping && typingName" class="text-xs text-muted-foreground">
            {{ typingName }} is typing...
          </div>
        </div>
        <div class="border-t px-4 py-3 bg-card sticky bottom-0">
          <div class="flex items-end gap-2">
            <Textarea
              v-model="messageText"
              placeholder="Type your message..."
              rows="1"
              class="h-10 min-h-10 resize-none"
              @input="handleTypingInput"
              @keydown.enter.exact.prevent="sendMessage"
            />
            <Button :disabled="!canSend" class="h-10 px-4" @click="sendMessage">
              <SendHorizontal class="mr-2 h-4 w-4" /> Send
            </Button>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
