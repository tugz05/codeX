<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Loader2, Save, ArrowLeft, FileText, X, Trash2 } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  material: {
    id: number
    title: string
    description: string | null
    type: string
    url: string | null
    video_url: string | null
    embed_code: string | null
    accessible_date: string | null
    accessible_time: string | null
    attachments: Array<{ id: number; name: string; type: string; url: string; size: number | null }>
  }
}>()

const form = useForm({
  title: props.material.title,
  description: props.material.description ?? '',
  type: props.material.type,
  url: props.material.url ?? null,
  video_url: props.material.video_url ?? null,
  embed_code: props.material.embed_code ?? null,
  accessible_date: props.material.accessible_date ?? null,
  accessible_time: props.material.accessible_time ?? null,
  attachments: [] as File[],
  attachments_remove: [] as number[],
})

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
    // Clear new files if switching to link or video
    if (newType === 'link' || newType === 'video') {
      selectedFiles.value = []
      form.attachments = []
      if (fileInputRef.value) {
        fileInputRef.value.value = ''
      }
    }
  }
})

const accessToggle = ref(!!(form.accessible_date || form.accessible_time))
const fileInputRef = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])
const attachmentsToRemove = ref<number[]>([])

const processing = computed(() => form.processing)

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files) {
    selectedFiles.value = Array.from(target.files)
    form.attachments = selectedFiles.value
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1)
  form.attachments = selectedFiles.value
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function removeExistingAttachment(id: number) {
  if (!attachmentsToRemove.value.includes(id)) {
    attachmentsToRemove.value.push(id)
    form.attachments_remove = attachmentsToRemove.value
  }
}

function formatFileSize(bytes: number | null): string {
  if (!bytes) return 'Unknown size'
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

  form.put(route('instructor.materials.update', [props.classlist.id, props.material.id]), {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Material updated successfully')
      router.visit(route('instructor.activities.index', props.classlist.id))
    },
    onError: () => {
      toast.error('Failed to update material')
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Edit Material" />

    <div class="space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="route('instructor.activities.index', classlist.id)">
          <Button variant="outline" size="sm">
            <ArrowLeft class="h-4 w-4 mr-1" /> Back
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold">Edit Material</h1>
          <p class="text-muted-foreground">{{ classlist.name }} - {{ classlist.room }}</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Material Details</CardTitle>
          <CardDescription>Update the material information</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-2">
              <Label for="title">Title <span class="text-destructive">*</span></Label>
              <Input id="title" v-model="form.title" required />
              <Alert v-if="form.errors.title" variant="destructive">
                <AlertDescription>{{ form.errors.title }}</AlertDescription>
              </Alert>
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="form.description" rows="4" />
            </div>

            <div class="space-y-2">
              <Label for="type">Type</Label>
              <Select v-model="form.type">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="resource">Resource</SelectItem>
                  <SelectItem value="link">Link</SelectItem>
                  <SelectItem value="document">Document</SelectItem>
                  <SelectItem value="video">Video</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
              <p class="text-xs text-muted-foreground">
                <span v-if="form.type === 'resource'">Upload files or provide resources for students</span>
                <span v-else-if="form.type === 'link'">Add an external link/URL</span>
                <span v-else-if="form.type === 'document'">Upload document files (PDF, Word, etc.)</span>
                <span v-else-if="form.type === 'video'">Add video URL or embed code</span>
                <span v-else-if="form.type === 'other'">Upload any type of file</span>
              </p>
            </div>

            <!-- Link URL Field (shown when type is 'link') -->
            <div v-if="showLinkField" class="space-y-2">
              <Label for="url">Link URL <span class="text-destructive">*</span></Label>
              <Input 
                id="url" 
                v-model="form.url" 
                type="url" 
                placeholder="https://example.com" 
                required 
              />
              <p class="text-xs text-muted-foreground">Enter the full URL including https://</p>
              <Alert v-if="form.errors.url" variant="destructive">
                <AlertDescription>{{ form.errors.url }}</AlertDescription>
              </Alert>
            </div>

            <!-- Video Fields (shown when type is 'video') -->
            <div v-if="showVideoFields" class="space-y-4">
              <div class="space-y-2">
                <Label for="video_url">Video URL</Label>
                <Input 
                  id="video_url" 
                  v-model="form.video_url" 
                  type="url" 
                  placeholder="https://youtube.com/watch?v=..." 
                />
                <p class="text-xs text-muted-foreground">YouTube, Vimeo, or other video platform URL</p>
                <Alert v-if="form.errors.video_url" variant="destructive">
                  <AlertDescription>{{ form.errors.video_url }}</AlertDescription>
                </Alert>
              </div>
              <div class="space-y-2">
                <Label for="embed_code">Or Embed Code</Label>
                <Textarea 
                  id="embed_code" 
                  v-model="form.embed_code" 
                  rows="4" 
                  placeholder="<iframe src='...'></iframe>" 
                />
                <p class="text-xs text-muted-foreground">Paste the iframe embed code here</p>
                <Alert v-if="form.errors.embed_code" variant="destructive">
                  <AlertDescription>{{ form.errors.embed_code }}</AlertDescription>
                </Alert>
              </div>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <Label>Set Access Date/Time</Label>
                <input type="checkbox" v-model="accessToggle" class="rounded" />
              </div>
              <div v-if="accessToggle" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="accessible_date">Accessible Date</Label>
                  <Input id="accessible_date" type="date" v-model="form.accessible_date" />
                </div>
                <div class="space-y-2">
                  <Label for="accessible_time">Accessible Time</Label>
                  <Input id="accessible_time" type="time" v-model="form.accessible_time" />
                </div>
              </div>
            </div>

            <!-- Existing Attachments -->
            <div v-if="material.attachments.length > 0" class="space-y-2">
              <Label>Existing Attachments</Label>
              <div class="space-y-2">
                <div
                  v-for="attachment in material.attachments"
                  :key="attachment.id"
                  class="flex items-center justify-between p-2 bg-muted rounded-md"
                  :class="{ 'opacity-50': attachmentsToRemove.includes(attachment.id) }"
                >
                  <div class="flex items-center gap-2">
                    <FileText class="h-4 w-4" />
                    <a :href="attachment.url" target="_blank" class="text-sm hover:underline">
                      {{ attachment.name }}
                    </a>
                    <span class="text-xs text-muted-foreground">({{ formatFileSize(attachment.size) }})</span>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeExistingAttachment(attachment.id)"
                  >
                    <Trash2 class="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              </div>
            </div>

            <!-- New Attachments (shown for resource, document, other types) -->
            <div v-if="showFileUpload" class="space-y-2">
              <Label>Add New Files</Label>
              <Input
                ref="fileInputRef"
                type="file"
                multiple
                @change="handleFileChange"
              />
              <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="index"
                  class="flex items-center justify-between p-2 bg-muted rounded-md"
                >
                  <div class="flex items-center gap-2">
                    <FileText class="h-4 w-4" />
                    <span class="text-sm">{{ file.name }}</span>
                    <span class="text-xs text-muted-foreground">({{ formatFileSize(file.size) }})</span>
                  </div>
                  <Button type="button" variant="ghost" size="sm" @click="removeFile(index)">
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-4">
              <Link :href="route('instructor.activities.index', classlist.id)">
                <Button type="button" variant="outline" :disabled="processing">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="processing">
                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ processing ? 'Updating...' : 'Update Material' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
