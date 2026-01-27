<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, FileText, Download, Calendar, User, Eye } from 'lucide-vue-next'

const props = defineProps<{
  classlist: {
    id: string
    name: string
    room: string
    academic_year: string
  }
  materials: Array<{
    id: number
    title: string
    description: string | null
    type: string
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    author: {
      id: number
      name: string
    }
    attachments: Array<{
      id: number
      name: string
      type: string
      url: string
      size: number | null
    }>
  }>
}>()

const { classlist, materials } = props

function isPreviewable(attachment: { name: string; type: string | null }): boolean {
  const name = attachment.name?.toLowerCase() || ''
  const type = attachment.type?.toLowerCase() || ''
  if (type.includes('pdf') || name.endsWith('.pdf')) return true
  if (type.startsWith('image/')) return true
  if (type.startsWith('video/')) return true
  if (/\.(png|jpe?g|gif|webp|bmp|svg)$/.test(name)) return true
  if (/\.(mp4|webm|ogg|mov|m4v)$/.test(name)) return true
  if (/\.(pptx?|docx?|xlsx?)$/.test(name)) return true
  return false
}

function getPreviewUrl(materialId: number, attachmentId: number, attachmentName: string): string {
  const downloadUrl = route('student.materials.attachments.download', [props.classlist.id, materialId, attachmentId])
  const name = attachmentName?.toLowerCase() || ''
  if (/\.(pptx?|docx?|xlsx?)$/.test(name)) {
    return `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(downloadUrl)}`
  }
  return `${downloadUrl}?preview=1`
}

function formatFileSize(bytes: number | null): string {
  if (!bytes) return 'Unknown size'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function formatDate(date: string | null): string {
  if (!date) return 'Available now'
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <AppLayout>
    <Head title="Materials" />

    <div class="space-y-6">
      <div class="flex items-center gap-4">
        <Link :href="route('student.classlist')">
          <Button variant="outline" size="sm">
            <ArrowLeft class="h-4 w-4 mr-1" /> Back
          </Button>
        </Link>
        <div>
          <h1 class="text-3xl font-bold">Materials</h1>
          <p class="text-muted-foreground">{{ classlist.name }} - {{ classlist.room }}</p>
        </div>
      </div>

      <div v-if="materials.length === 0" class="text-center py-12">
        <FileText class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
        <p class="text-muted-foreground">No materials available yet.</p>
      </div>

      <div v-else class="grid gap-4">
        <Card v-for="material in materials" :key="material.id" class="hover:shadow-md transition-shadow">
          <CardHeader>
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <CardTitle class="text-xl">{{ material.title }}</CardTitle>
                  <Badge variant="outline">{{ material.type }}</Badge>
                </div>
                <CardDescription v-if="material.description" class="line-clamp-2">
                  {{ material.description }}
                </CardDescription>
                <div class="flex items-center gap-4 mt-3 text-sm text-muted-foreground">
                  <span class="flex items-center gap-1">
                    <Calendar class="h-4 w-4" />
                    {{ formatDate(material.accessible_date) }}
                  </span>
                  <span class="flex items-center gap-1">
                    <User class="h-4 w-4" />
                    {{ material.author.name }}
                  </span>
                  <span v-if="material.attachments.length > 0">
                    {{ material.attachments.length }} file(s)
                  </span>
                </div>
              </div>
              <Link :href="route('student.materials.show', [classlist.id, material.id])">
                <Button variant="ghost" size="sm">
                  <Eye class="h-4 w-4 mr-2" />
                  View
                </Button>
              </Link>
            </div>
          </CardHeader>
          <CardContent v-if="material.attachments.length > 0">
            <div class="space-y-2">
              <p class="text-sm font-medium">Attachments:</p>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="attachment in material.attachments"
                  :key="attachment.id"
                  class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-muted px-3 py-2 text-sm"
                >
                  <div class="flex min-w-0 items-center gap-2">
                    <Download class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ attachment.name }}</span>
                    <span class="text-muted-foreground">({{ formatFileSize(attachment.size) }})</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Link
                      v-if="isPreviewable(attachment)"
                      :href="getPreviewUrl(material.id, attachment.id, attachment.name)"
                      target="_blank"
                      as="button"
                    >
                      <Button variant="outline" size="sm">
                        <Eye class="h-4 w-4 mr-1" />
                        Preview
                      </Button>
                    </Link>
                    <Link
                      :href="route('student.materials.attachments.download', [classlist.id, material.id, attachment.id])"
                      as="button"
                    >
                      <Button variant="ghost" size="sm">
                        <Download class="h-4 w-4 mr-1" />
                        Download
                      </Button>
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
