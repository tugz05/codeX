<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Loader2, Save, ArrowLeft, FileText, X, UploadCloud } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  other_classlists: Array<{ id: number; name: string; room: string; section: string | null; academic_year: string | null }>
}>()

const form = useForm({
  title: '',
  description: '',
  type: 'resource',
  url: '' as string | null, // For link type
  video_url: '' as string | null, // For video type
  embed_code: '' as string | null, // For video embed
  accessible_date: null as string | null,
  accessible_time: null as string | null,
  attachments: [] as File[],
  also_classlist_ids: [] as number[],
})

const accessToggle = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const isDragging = ref(false)

const processing = computed(() => form.processing)

// Computed properties for showing/hiding fields based on type
const showFileUpload = computed(() => {
  return form.type === 'resource' || form.type === 'document' || form.type === 'other'
})

const showLinkField = computed(() => {
  return form.type === 'link'
})

const showVideoFields = computed(() => {
  return form.type === 'video'
})

// Clear fields when type changes
watch(() => form.type, (newType, oldType) => {
  if (newType !== oldType) {
    // Clear URL if switching away from link
    if (oldType === 'link') {
      form.url = null
    }
    // Clear video fields if switching away from video
    if (oldType === 'video') {
      form.video_url = null
      form.embed_code = null
    }
    // Clear files if switching to link or video
    if (newType === 'link' || newType === 'video') {
      selectedFiles.value = []
      form.attachments = []
      if (fileInputRef.value) {
        fileInputRef.value.value = ''
      }
    }
  }
})

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files) {
    handleFiles(target.files)
  }
}

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
  // Validate based on type
  if (form.type === 'link' && !form.url) {
    toast.error('Please provide a URL for link type materials')
    return
  }
  
  if (form.type === 'video' && !form.video_url && !form.embed_code) {
    toast.error('Please provide either a video URL or embed code for video type materials')
    return
  }

  form.post(route('instructor.materials.store', props.classlist.id), {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Material created successfully')
      router.visit(route('instructor.activities.index', props.classlist.id))
    },
    onError: () => {
      toast.error('Failed to create material')
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Create Material" />

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
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Create Material</h1>
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
            <CardTitle class="text-xl">Material Details</CardTitle>
            <CardDescription>Create a new learning resource for your students</CardDescription>
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
                        placeholder="e.g., Chapter 1 Notes" 
                        required 
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                      />
                      <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.title }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                      <Label for="description" class="text-sm font-medium">Description</Label>
                    <RichTextEditor
                      v-model="form.description"
                      placeholder="Add a description for this material..."
                      min-height="160px"
                    />
                      <Alert v-if="form.errors.description" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.description }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Type -->
                    <div class="space-y-2">
                      <Label for="type" class="text-sm font-medium">Material Type <span class="text-destructive">*</span></Label>
                      <Select v-model="form.type">
                        <SelectTrigger class="border-2 transition-all duration-300 focus:scale-[1.01]">
                          <SelectValue placeholder="Select material type" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="resource">Resource</SelectItem>
                          <SelectItem value="link">Link</SelectItem>
                          <SelectItem value="document">Document</SelectItem>
                          <SelectItem value="video">Video</SelectItem>
                          <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                      </Select>
                      <p class="text-xs text-muted-foreground mt-1.5 pl-1">
                        <span v-if="form.type === 'resource'">Upload files or provide resources for students</span>
                        <span v-else-if="form.type === 'link'">Add an external link/URL</span>
                        <span v-else-if="form.type === 'document'">Upload document files (PDF, Word, etc.)</span>
                        <span v-else-if="form.type === 'video'">Add video URL or embed code</span>
                        <span v-else-if="form.type === 'other'">Upload any type of file</span>
                      </p>
                    </div>

                    <!-- Multi-class posting -->
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
                  </div>
                </div>
              </div>

              <!-- Type-Specific Fields Section -->
              <div v-if="showLinkField || showVideoFields" class="space-y-6 pt-2">
                <div>
                  <h3 class="text-base font-semibold mb-4 pb-2 border-b">
                    <span v-if="showLinkField">Link Information</span>
                    <span v-else-if="showVideoFields">Video Information</span>
                  </h3>
                  <div class="space-y-5">
                    <!-- Link URL Field (shown when type is 'link') -->
                    <div v-if="showLinkField" class="space-y-2">
                      <Label for="url" class="text-sm font-medium">
                        Link URL <span class="text-destructive">*</span>
                      </Label>
                      <Input 
                        id="url" 
                        v-model="form.url" 
                        type="url" 
                        placeholder="https://example.com" 
                        required 
                        class="border-2 transition-all duration-300 focus:scale-[1.01]"
                      />
                      <p class="text-xs text-muted-foreground mt-1.5 pl-1">Enter the full URL including https://</p>
                      <Alert v-if="form.errors.url" variant="destructive" class="mt-2">
                        <AlertDescription>{{ form.errors.url }}</AlertDescription>
                      </Alert>
                    </div>

                    <!-- Video Fields (shown when type is 'video') -->
                    <div v-if="showVideoFields" class="space-y-5">
                      <div class="space-y-2">
                        <Label for="video_url" class="text-sm font-medium">Video URL</Label>
                        <Input 
                          id="video_url" 
                          v-model="form.video_url" 
                          type="url" 
                          placeholder="https://youtube.com/watch?v=..." 
                          class="border-2 transition-all duration-300 focus:scale-[1.01]"
                        />
                        <p class="text-xs text-muted-foreground mt-1.5 pl-1">YouTube, Vimeo, or other video platform URL</p>
                        <Alert v-if="form.errors.video_url" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.video_url }}</AlertDescription>
                        </Alert>
                      </div>
                      <div class="space-y-2">
                        <Label for="embed_code" class="text-sm font-medium">Or Embed Code</Label>
                        <Textarea 
                          id="embed_code" 
                          v-model="form.embed_code" 
                          rows="4" 
                          placeholder="<iframe src='...'></iframe>" 
                          class="border-2 transition-all duration-300 focus:scale-[1.01] resize-none"
                        />
                        <p class="text-xs text-muted-foreground mt-1.5 pl-1">Paste the iframe embed code here</p>
                        <Alert v-if="form.errors.embed_code" variant="destructive" class="mt-2">
                          <AlertDescription>{{ form.errors.embed_code }}</AlertDescription>
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
                        <p class="text-xs text-muted-foreground">Control when students can access this material</p>
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

              <!-- Attachments Section (shown for resource, document, other types) -->
              <div v-if="showFileUpload" class="space-y-6 pt-2">
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
                          @change="handleFileChange"
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
                  {{ processing ? 'Creating...' : 'Create Material' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
