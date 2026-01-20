<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, Download, Calendar, User, NotebookPen, FileText, Clock, UploadCloud, X, ExternalLink, Video, Save, Loader2 } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'
import CommentSection from '@/components/CommentSection.vue'
import FileList from '@/components/FileList.vue'
import FileFolderManager from '@/components/FileFolderManager.vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  assignment: {
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    author: { id: number; name: string }
    attachments: Array<{
      id: number
      name: string
      type: string
      url: string
      size: number | null
    }>
    comments?: Array<{
      id: number
      content: string
      created_at: string
      user: { id: number; name: string }
      replies?: Array<{
        id: number
        content: string
        created_at: string
        user: { id: number; name: string }
      }>
    }>
  }
  submission?: {
    id: number
    submission_type: 'file' | 'link' | 'video_link'
    link_url: string | null
    video_url: string | null
    status: 'draft' | 'submitted' | 'graded'
    score: number | null
    feedback: string | null
    submitted_at: string | null
    attachments: Array<{
      id: number
      name: string
      type: string
      url: string
      size: number | null
      version?: number
      is_current?: boolean
      folder_id?: number | null
      versions?: Array<{
        id: number
        version: number
        name: string
        url: string
        type: string | null
        size: number | null
        version_notes: string | null
        is_current: boolean
        created_at: string
      }>
    }>
  } | null
  folders?: Array<{
    id: number
    name: string
    description: string | null
    parent_id: number | null
  }>
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const isDragging = ref(false)

const form = useForm({
  submission_type: props.submission?.submission_type || 'file',
  link_url: props.submission?.link_url || '',
  video_url: props.submission?.video_url || '',
  attachments: [] as File[],
  attachments_remove: [] as number[],
})

const showLinkField = computed(() => form.submission_type === 'link')
const showVideoField = computed(() => form.submission_type === 'video_link')
const showFileUpload = computed(() => form.submission_type === 'file')

// Watch submission type changes to clear irrelevant fields
watch(() => form.submission_type, (newType) => {
  if (newType !== 'link') form.link_url = ''
  if (newType !== 'video_link') form.video_url = ''
  if (newType !== 'file') {
    selectedFiles.value = []
    form.attachments = []
  }
})

function formatFileSize(bytes: number | null): string {
  if (!bytes) return 'Unknown size'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function formatDate(date: string | null): string {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function formatTime(value?: string | null): string {
  if (!value) return ''
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
    const [hh, mm] = value.split(':')
    const d = new Date()
    d.setHours(Number(hh), Number(mm), 0, 0)
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  }
  const d = new Date(value)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  if (e.dataTransfer?.files) {
    handleFiles(Array.from(e.dataTransfer.files))
  }
}

function openFilePicker() {
  fileInputRef.value?.click()
}

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files) {
    handleFiles(Array.from(target.files))
  }
}

function handleFiles(files: File[]) {
  const validFiles = files.filter(file => {
    const maxSize = 50 * 1024 * 1024 // 50MB
    if (file.size > maxSize) {
      alert(`${file.name} is too large. Maximum file size is 50MB.`)
      return false
    }
    return true
  })
  selectedFiles.value = [...selectedFiles.value, ...validFiles]
  form.attachments = selectedFiles.value
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1)
  form.attachments = selectedFiles.value
}

function removeExistingAttachment(attachmentId: number) {
  if (!form.attachments_remove.includes(attachmentId)) {
    form.attachments_remove.push(attachmentId)
  }
}

function submit() {
  if (props.submission) {
    form.put(route('student.assignments.submissions.update', [props.classlist.id, props.assignment.id, props.submission.id]), {
      forceFormData: true,
    })
  } else {
    form.post(route('student.assignments.submit', [props.classlist.id, props.assignment.id]), {
      forceFormData: true,
    })
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="`${assignment.title} · ${classlist.name}`" />

    <!-- Full canvas with subtle gradient + sticky header -->
    <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
      <!-- Header -->
      <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-3 sm:px-4 md:px-6 py-3 md:py-4">
          <div class="flex min-w-0 items-center gap-3">
            <Link :href="route('student.activities.index', classlist.id)" as="button" aria-label="Back to activities">
              <Button variant="outline" size="sm">
                <ArrowLeft class="mr-1 h-4 w-4" /> Back
              </Button>
            </Link>
            <div class="min-w-0">
              <h1 class="truncate text-base font-semibold md:text-lg">
                {{ assignment.title }}
              </h1>
              <p class="truncate text-xs text-muted-foreground md:text-sm">
                {{ classlist.name }} • AY {{ classlist.academic_year }} • Room {{ classlist.room }}
              </p>
            </div>
          </div>

          <div class="hidden shrink-0 text-right text-xs text-muted-foreground md:block">
            Posted: {{ formatDate(assignment.created_at) }}
          </div>
        </div>
      </header>

      <!-- Main content -->
      <main class="mx-auto w-full max-w-[1600px] px-3 sm:px-4 md:px-6 py-4 sm:py-6 md:py-8">
        <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-12">
          <!-- Main content -->
          <section class="md:col-span-8">
            <div class="rounded-2xl border bg-card shadow-sm">
              <!-- Assignment Type Badge -->
              <div class="border-b px-5 py-4 md:px-6">
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-2">
                    <NotebookPen class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium text-muted-foreground">Assignment</span>
                  </div>
                </div>
              </div>

              <!-- Instruction -->
              <div class="px-5 py-5 md:px-6 md:py-6">
                <div v-if="assignment.instruction" class="prose prose-sm dark:prose-invert max-w-none">
                  <p class="leading-7 whitespace-pre-line text-foreground">
                    {{ assignment.instruction }}
                  </p>
                </div>
                <div v-else class="text-sm text-muted-foreground">No instructions provided for this assignment.</div>

                <!-- Attachments -->
                <div v-if="assignment.attachments.length > 0" class="mt-6 space-y-3">
                  <div class="flex items-center gap-2 text-sm font-medium">
                    <FileText class="h-4 w-4" />
                    <span>Attachments ({{ assignment.attachments.length }})</span>
                  </div>
                  <FileList
                    :files="assignment.attachments"
                    :can-edit="false"
                  />
                </div>
              </div>

              <!-- Submission Form -->
              <div class="mt-6 rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4 md:px-6">
                  <h2 class="text-lg font-semibold">
                    {{ props.submission ? 'Update Submission' : 'Submit Assignment' }}
                  </h2>
                  <p class="text-sm text-muted-foreground">Submit your work by uploading files, providing a link, or sharing a video link</p>
                </div>

                <form @submit.prevent="submit" class="px-5 py-5 md:px-6 md:py-6">
                  <div class="space-y-6">
                    <!-- Submission Type -->
                    <div class="space-y-2">
                      <Label for="submission_type" class="text-sm font-medium">Submission Type <span class="text-destructive">*</span></Label>
                      <Select v-model="form.submission_type">
                        <SelectTrigger class="border-2">
                          <SelectValue placeholder="Select submission type" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="file">Upload Files</SelectItem>
                          <SelectItem value="link">Share Link</SelectItem>
                          <SelectItem value="video_link">Video Link</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <!-- Link URL Field -->
                    <div v-if="showLinkField" class="space-y-2">
                      <Label for="link_url" class="text-sm font-medium">Link URL <span class="text-destructive">*</span></Label>
                      <Input
                        id="link_url"
                        v-model="form.link_url"
                        type="url"
                        placeholder="https://example.com"
                        required
                        class="border-2"
                      />
                      <Alert v-if="form.errors.link_url" variant="destructive">
                        <AlertDescription>{{ form.errors.link_url }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Video URL Field -->
                    <div v-if="showVideoField" class="space-y-2">
                      <Label for="video_url" class="text-sm font-medium">Video URL <span class="text-destructive">*</span></Label>
                      <Input
                        id="video_url"
                        v-model="form.video_url"
                        type="url"
                        placeholder="https://youtube.com/watch?v=..."
                        required
                        class="border-2"
                      />
                      <Alert v-if="form.errors.video_url" variant="destructive">
                        <AlertDescription>{{ form.errors.video_url }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- File Upload -->
                    <div v-if="showFileUpload" class="space-y-4">
                      <Label class="text-sm font-medium">Upload Files</Label>
                      
                      <!-- File Folder Manager -->
                      <div v-if="props.submission && props.folders && showFileUpload" class="mb-4 p-4 border rounded-lg bg-muted/30">
                        <FileFolderManager
                          :folders="props.folders"
                          :files="props.submission.attachments.filter(a => !form.attachments_remove.includes(a.id)).map(a => ({ ...a, submission_id: props.submission!.id }))"
                          :current-folder-id="null"
                        />
                      </div>

                      <!-- Existing Attachments (if editing) -->
                      <div v-if="props.submission && props.submission.attachments.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Current Files</p>
                        <FileList
                          :files="props.submission.attachments.filter(a => !form.attachments_remove.includes(a.id))"
                          :submission-id="props.submission.id"
                          :can-edit="props.submission.status !== 'graded'"
                          :folders="props.folders || []"
                          @file-removed="removeExistingAttachment"
                        />
                      </div>

                      <!-- Drag and Drop Area -->
                      <div
                        class="rounded-xl border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                        :class="isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/30 hover:bg-muted/30'"
                        @dragover.prevent="onDragOver"
                        @dragleave.prevent="onDragLeave"
                        @drop.prevent="onDrop"
                        @click="openFilePicker"
                        role="button"
                        tabindex="0"
                        @keydown.enter.prevent="openFilePicker"
                        @keydown.space.prevent="openFilePicker"
                      >
                        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                          <UploadCloud class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <p class="text-sm font-medium">Choose a file or drag & drop it here</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                          All file formats, up to 50MB
                        </p>
                        <input
                          ref="fileInputRef"
                          type="file"
                          multiple
                          @change="handleFileChange"
                          class="hidden"
                        />
                      </div>

                      <!-- Selected Files List -->
                      <div v-if="selectedFiles.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">New Files ({{ selectedFiles.length }})</p>
                        <div class="space-y-2">
                          <div
                            v-for="(file, index) in selectedFiles"
                            :key="index"
                            class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border"
                          >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                              <FileText class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                              <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ file.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
                              </div>
                            </div>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              @click="removeFile(index)"
                              class="flex-shrink-0 hover:bg-destructive/10 hover:text-destructive"
                            >
                              <X class="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>

                      <Alert v-if="form.errors['attachments.0']" variant="destructive">
                        <AlertDescription>{{ form.errors['attachments.0'] }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                      <Button
                        type="submit"
                        :disabled="form.processing"
                        class="transition-all duration-200 hover:scale-105"
                      >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Submitting...' : (props.submission ? 'Update Submission' : 'Submit Assignment') }}
                      </Button>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Current Submission Display -->
              <div v-if="props.submission && props.submission.status === 'submitted'" class="mt-6 rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4 md:px-6">
                  <h2 class="text-lg font-semibold">Your Submission</h2>
                  <p class="text-sm text-muted-foreground">Submitted on {{ formatDate(props.submission.submitted_at) }}</p>
                </div>
                <div class="px-5 py-5 md:px-6 md:py-6 space-y-4">
                  <div class="flex items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 capitalize">
                      {{ props.submission.status }}
                    </span>
                    <span v-if="props.submission.score !== null" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                      Score: {{ props.submission.score }} / {{ props.assignment.points || 'N/A' }}
                    </span>
                  </div>

                  <!-- Link Submission -->
                  <div v-if="props.submission.submission_type === 'link' && props.submission.link_url" class="space-y-2">
                    <Label class="text-sm font-medium">Submitted Link</Label>
                    <a
                      :href="props.submission.link_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="flex items-center gap-3 p-4 bg-primary/5 border border-primary/20 rounded-lg hover:bg-primary/10 transition-colors"
                    >
                      <ExternalLink class="h-5 w-5 text-primary" />
                      <span class="break-all text-sm font-medium">{{ props.submission.link_url }}</span>
                    </a>
                  </div>

                  <!-- Video Submission -->
                  <div v-if="props.submission.submission_type === 'video_link' && props.submission.video_url" class="space-y-2">
                    <Label class="text-sm font-medium">Submitted Video</Label>
                    <a
                      :href="props.submission.video_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="flex items-center gap-3 p-4 bg-primary/5 border border-primary/20 rounded-lg hover:bg-primary/10 transition-colors"
                    >
                      <Video class="h-5 w-5 text-primary" />
                      <span class="break-all text-sm font-medium">{{ props.submission.video_url }}</span>
                    </a>
                  </div>

                  <!-- File Attachments -->
                  <div v-if="props.submission.submission_type === 'file' && props.submission.attachments.length > 0" class="space-y-2">
                    <Label class="text-sm font-medium">Submitted Files</Label>
                    <FileList
                      :files="props.submission.attachments"
                      :submission-id="props.submission.id"
                      :can-edit="props.submission.status !== 'graded'"
                      :folders="props.folders || []"
                    />
                  </div>

                  <!-- Feedback -->
                  <div v-if="props.submission.feedback" class="space-y-2">
                    <Label class="text-sm font-medium">Feedback</Label>
                    <div class="p-4 bg-muted/50 rounded-lg border">
                      <p class="text-sm whitespace-pre-line">{{ props.submission.feedback }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Sidebar -->
          <aside class="md:col-span-4">
            <div class="space-y-4">
              <!-- Assignment Info Card -->
              <Card class="border-2">
                <CardHeader class="pb-3">
                  <CardTitle class="text-base">Assignment Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-start gap-3">
                      <User class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Posted by</p>
                        <p class="text-sm font-medium">{{ assignment.author.name }}</p>
                      </div>
                    </div>
                    <div class="flex items-start gap-3">
                      <Clock class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Created</p>
                        <p class="text-sm font-medium">{{ formatDate(assignment.created_at) }}</p>
                      </div>
                    </div>
                    <div v-if="assignment.points !== null" class="flex items-start gap-3">
                      <FileText class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Points</p>
                        <p class="text-sm font-medium">{{ assignment.points }} points</p>
                      </div>
                    </div>
                    <div v-if="assignment.due_date" class="flex items-start gap-3">
                      <Calendar class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Due Date</p>
                        <p class="text-sm font-medium">
                          {{ formatDate(assignment.due_date) }}
                          <span v-if="assignment.due_time"> at {{ formatTime(assignment.due_time) }}</span>
                        </p>
                      </div>
                    </div>
                    <div v-if="assignment.accessible_date" class="flex items-start gap-3">
                      <Calendar class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Accessible from</p>
                        <p class="text-sm font-medium">
                          {{ formatDate(assignment.accessible_date) }}
                          <span v-if="assignment.accessible_time"> at {{ formatTime(assignment.accessible_time) }}</span>
                        </p>
                      </div>
                    </div>
                    <div v-else-if="!assignment.accessible_date" class="flex items-start gap-3">
                      <Calendar class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Accessible</p>
                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Available now</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </aside>
        </div>

        <!-- Comments Section -->
        <div class="mt-6">
          <CommentSection
            :comments="assignment.comments || []"
            commentable-type="App\\Models\\Assignment"
            :commentable-id="assignment.id"
            :classlist-id="classlist.id"
          />
        </div>
      </main>
    </div>
  </AppLayout>
</template>
