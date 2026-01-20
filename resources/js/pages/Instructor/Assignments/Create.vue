<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Loader2, Save, ArrowLeft, FileText, X, UploadCloud } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
}>()

const form = useForm({
  title: '',
  instruction: '',
  points: null as number | null,
  due_date: null as string | null,
  due_time: null as string | null,
  accessible_date: null as string | null,
  accessible_time: null as string | null,
  attachments: [] as File[],
})

const accessToggle = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const isDragging = ref(false)

const processing = computed(() => form.processing)

function handleFiles(fileList: FileList | File[]) {
  const files = Array.from(fileList)
  const maxSize = 50 * 1024 * 1024 // 50MB
  
  const validFiles = files.filter(file => {
    if (file.size > maxSize) {
      toast.error(`File "${file.name}" exceeds 50MB limit`)
      return false
    }
    return true
  })
  
  selectedFiles.value = [...selectedFiles.value, ...validFiles]
  form.attachments = selectedFiles.value
}

function openFilePicker() {
  fileInputRef.value?.click()
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
  
  if (e.dataTransfer?.files) {
    handleFiles(e.dataTransfer.files)
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1)
  form.attachments = selectedFiles.value
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function submit() {
  form.post(route('instructor.assignments.store', props.classlist.id), {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Assignment created successfully')
      router.visit(route('instructor.activities.index', props.classlist.id))
    },
    onError: () => {
      toast.error('Failed to create assignment')
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Create Assignment" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
      <!-- Header -->
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link :href="route('instructor.activities.index', classlist.id)">
            <Button variant="outline" size="sm" class="transition-all duration-200 hover:scale-105">
              <ArrowLeft class="h-4 w-4 mr-1" /> Back
            </Button>
          </Link>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Create Assignment</h1>
            <p class="text-sm text-muted-foreground mt-1">
              {{ classlist.name }} • Room {{ classlist.room }} • AY {{ classlist.academic_year }}
            </p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="flex-1">
        <Card class="border-2 shadow-sm">
          <CardHeader class="pb-4">
            <CardTitle class="text-xl">Assignment Details</CardTitle>
            <CardDescription>Create a new assignment for your students</CardDescription>
          </CardHeader>
          <CardContent class="pt-6">
            <form @submit.prevent="submit" class="space-y-8">
              <!-- Basic Information Section -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-base font-semibold mb-4 pb-2 border-b">Basic Information</h3>
                  <div class="space-y-5">
                    <!-- Title -->
                    <div class="space-y-2">
                      <Label for="title" class="text-sm font-medium">
                        Title <span class="text-destructive">*</span>
                      </Label>
                      <Input 
                        id="title" 
                        v-model="form.title" 
                        placeholder="e.g., Chapter 1 Assignment" 
                        required 
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                      />
                      <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.title }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Instruction -->
                    <div class="space-y-2">
                      <Label for="instruction" class="text-sm font-medium">Instructions</Label>
                      <Textarea
                        id="instruction"
                        v-model="form.instruction"
                        rows="6"
                        placeholder="Provide detailed instructions for this assignment..."
                        class="border-2 transition-all duration-300 focus:scale-[1.01] resize-none"
                      />
                      <Alert v-if="form.errors.instruction" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.instruction }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Points -->
                    <div class="space-y-2">
                      <Label for="points" class="text-sm font-medium">Points</Label>
                      <Input 
                        id="points" 
                        type="number" 
                        min="0"
                        v-model.number="form.points" 
                        placeholder="e.g., 100" 
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                      />
                      <Alert v-if="form.errors.points" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.points }}</AlertDescription>
                      </Alert>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Due Date/Time Section -->
              <div class="space-y-6 pt-2">
                <div>
                  <h3 class="text-base font-semibold mb-4 pb-2 border-b">Due Date & Time</h3>
                  <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                      <div class="space-y-2">
                        <Label for="due_date" class="text-sm font-medium">Due Date</Label>
                        <Input 
                          id="due_date" 
                          type="date" 
                          v-model="form.due_date" 
                          class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <Alert v-if="form.errors.due_date" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.due_date }}</AlertDescription>
                        </Alert>
                      </div>
                      <div class="space-y-2">
                        <Label for="due_time" class="text-sm font-medium">Due Time</Label>
                        <Input 
                          id="due_time" 
                          type="time" 
                          v-model="form.due_time" 
                          class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <Alert v-if="form.errors.due_time" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.due_time }}</AlertDescription>
                        </Alert>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Access Control Section -->
              <div class="space-y-6 pt-2">
                <div>
                  <h3 class="text-base font-semibold mb-4 pb-2 border-b">Access Control</h3>
                  <div class="space-y-5">
                    <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg border">
                      <div class="space-y-0.5">
                        <Label class="text-sm font-medium cursor-pointer">Set Access Date/Time</Label>
                        <p class="text-xs text-muted-foreground">Control when students can access this assignment</p>
                      </div>
                      <input 
                        type="checkbox" 
                        v-model="accessToggle" 
                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary cursor-pointer" 
                      />
                    </div>

                    <div v-if="accessToggle" class="grid grid-cols-1 gap-5 sm:grid-cols-2 pt-2">
                      <div class="space-y-2">
                        <Label for="accessible_date" class="text-sm font-medium">Accessible Date</Label>
                        <Input 
                          id="accessible_date" 
                          type="date" 
                          v-model="form.accessible_date" 
                          class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <Alert v-if="form.errors.accessible_date" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.accessible_date }}</AlertDescription>
                        </Alert>
                      </div>
                      <div class="space-y-2">
                        <Label for="accessible_time" class="text-sm font-medium">Accessible Time</Label>
                        <Input 
                          id="accessible_time" 
                          type="time" 
                          v-model="form.accessible_time" 
                          class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <Alert v-if="form.errors.accessible_time" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.accessible_time }}</AlertDescription>
                        </Alert>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Attachments Section -->
              <div class="space-y-6 pt-2">
                <div>
                  <h3 class="text-base font-semibold mb-4 pb-2 border-b">File Attachments</h3>
                  <div class="space-y-5">
                    <div class="space-y-4">
                      <Label class="text-sm font-medium">Add more files</Label>
                      
                      <!-- Drag and Drop Area -->
                      <div
                        @dragover="onDragOver"
                        @dragleave="onDragLeave"
                        @drop="onDrop"
                        @click="openFilePicker"
                        :class="[
                          'relative rounded-xl border-2 border-dashed p-8 md:p-12 text-center cursor-pointer transition-all duration-300',
                          isDragging 
                            ? 'border-primary bg-primary/5 scale-[1.02]' 
                            : 'border-muted-foreground/30 hover:border-primary/50 hover:bg-muted/30'
                        ]"
                        role="button"
                        tabindex="0"
                        @keydown.enter.prevent="openFilePicker"
                        @keydown.space.prevent="openFilePicker"
                      >
                        <!-- Cloud Upload Icon -->
                        <div class="flex justify-center mb-4">
                          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted/50">
                            <UploadCloud class="h-6 w-6 text-muted-foreground" />
                          </div>
                        </div>
                        
                        <!-- Main Instruction Text -->
                        <p class="text-base font-semibold text-foreground mb-2">
                          Choose a file or drag & drop it here
                        </p>
                        
                        <!-- File Specifications -->
                        <p class="text-sm text-muted-foreground mb-6">
                          JPEG, PNG, PDF, and MP4 formats, up to 50MB
                        </p>
                        
                        <!-- Browse File Button -->
                        <div class="flex justify-center">
                          <Button 
                            type="button" 
                            variant="secondary" 
                            size="default"
                            class="shadow-sm hover:shadow-md transition-shadow"
                            @click.stop="openFilePicker"
                          >
                            Browse File
                          </Button>
                        </div>
                        
                        <!-- Hidden File Input -->
                        <input
                          ref="fileInputRef"
                          type="file"
                          multiple
                          @change="(e) => { const target = e.target as HTMLInputElement; if (target.files) handleFiles(target.files); }"
                          class="hidden"
                          accept=".jpeg,.jpg,.png,.pdf,.mp4,image/jpeg,image/png,application/pdf,video/mp4"
                        />
                      </div>
                      
                      <!-- Selected Files List -->
                      <div v-if="selectedFiles.length > 0" class="space-y-3">
                        <p class="text-sm font-medium text-foreground">Selected Files ({{ selectedFiles.length }})</p>
                        <div class="space-y-2">
                          <div
                            v-for="(file, index) in selectedFiles"
                            :key="index"
                            class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border border-border hover:bg-muted transition-colors"
                          >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                              <div class="flex-shrink-0">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                              </div>
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
                              class="flex-shrink-0 hover:bg-destructive/10 hover:text-destructive transition-colors"
                            >
                              <X class="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>

                      <Alert v-if="form.errors['attachments.0']" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors['attachments.0'] }}</AlertDescription>
                      </Alert>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t">
                <Link :href="route('instructor.activities.index', classlist.id)" class="w-full sm:w-auto">
                  <Button 
                    type="button" 
                    variant="outline" 
                    :disabled="processing" 
                    class="w-full sm:w-auto transition-all duration-200 hover:scale-105"
                  >
                    Cancel
                  </Button>
                </Link>
                <Button 
                  type="submit" 
                  :disabled="processing"
                  class="w-full sm:w-auto transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg"
                >
                  <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                  <Save v-else class="mr-2 h-4 w-4" />
                  {{ processing ? 'Creating...' : 'Create Assignment' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
