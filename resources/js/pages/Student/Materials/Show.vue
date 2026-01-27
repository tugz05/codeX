<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Download, Calendar, User, ExternalLink, Video, BookOpen, FileText, Clock } from 'lucide-vue-next'
import CommentSection from '@/components/CommentSection.vue'

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
}>()

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

function getPreviewUrl(attachmentId: number, attachmentName: string): string {
  const downloadUrl = route('student.materials.attachments.download', [classlist.id, material.id, attachmentId])
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
</script>

<template>
  <AppLayout>
    <Head :title="`${material.title} · ${classlist.name}`" />

    <!-- Full canvas with subtle gradient + sticky header -->
    <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
      <!-- Header -->
      <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-4 py-3 md:py-4">
          <div class="flex min-w-0 items-center gap-3">
            <Link :href="route('student.activities.index', classlist.id)" as="button" aria-label="Back to activities">
              <Button variant="outline" size="sm">
                <ArrowLeft class="mr-1 h-4 w-4" /> Back
              </Button>
            </Link>
            <div class="min-w-0">
              <h1 class="truncate text-base font-semibold md:text-lg">
                {{ material.title }}
              </h1>
              <p class="truncate text-xs text-muted-foreground md:text-sm">
                {{ classlist.name }} • AY {{ classlist.academic_year }} • Room {{ classlist.room }}
              </p>
            </div>
          </div>

          <div class="hidden shrink-0 text-right text-xs text-muted-foreground md:block">
            Posted: {{ formatDate(material.created_at) }}
          </div>
        </div>
      </header>

      <!-- Main content -->
      <main class="mx-auto w-full max-w-[1600px] px-3 sm:px-4 md:px-6 py-4 sm:py-6 md:py-8">
        <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-12">
          <!-- Main content -->
          <section class="md:col-span-8">
            <div class="rounded-2xl border bg-card shadow-sm">
              <!-- Material Type Badge -->
              <div class="border-b px-5 py-4 md:px-6">
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-2">
                    <BookOpen class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium text-muted-foreground">Material</span>
                  </div>
                  <Badge variant="outline" class="capitalize">{{ material.type }}</Badge>
                </div>
              </div>

              <!-- Description -->
              <div class="px-5 py-5 md:px-6 md:py-6">
                <div v-if="material.description" class="prose prose-sm dark:prose-invert max-w-none">
                  <p class="leading-7 whitespace-pre-line text-foreground">
                    {{ material.description }}
                  </p>
                </div>
                <div v-else class="text-sm text-muted-foreground">No description provided for this material.</div>

                <!-- Link URL (for link type) -->
                <div v-if="material.type === 'link' && material.url" class="mt-6 space-y-3">
                  <div class="flex items-center gap-2 text-sm font-medium">
                    <ExternalLink class="h-4 w-4" />
                    <span>External Link</span>
                  </div>
                  <a
                    :href="material.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-lg border-2 border-primary/20 bg-primary/5 p-4 transition-all hover:border-primary/40 hover:bg-primary/10"
                  >
                    <ExternalLink class="h-5 w-5 shrink-0 text-primary" />
                    <span class="break-all text-sm font-medium text-primary">{{ material.url }}</span>
                  </a>
                </div>

                <!-- Video (for video type) -->
                <div v-if="material.type === 'video'" class="mt-6 space-y-6">
                  <div v-if="material.video_url" class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                      <Video class="h-4 w-4" />
                      <span>Video URL</span>
                    </div>
                    <a
                      :href="material.video_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="flex items-center gap-3 rounded-lg border-2 border-primary/20 bg-primary/5 p-4 transition-all hover:border-primary/40 hover:bg-primary/10"
                    >
                      <Video class="h-5 w-5 shrink-0 text-primary" />
                      <span class="break-all text-sm font-medium text-primary">{{ material.video_url }}</span>
                    </a>
                  </div>
                  <div v-if="material.embed_code" class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                      <Video class="h-4 w-4" />
                      <span>Video Player</span>
                    </div>
                    <div class="overflow-hidden rounded-lg border bg-muted/50 p-4">
                      <div class="aspect-video w-full" v-html="material.embed_code"></div>
                    </div>
                  </div>
                </div>

                <!-- Attachments -->
                <div v-if="material.attachments.length > 0" class="mt-6 space-y-3">
                  <div class="flex items-center gap-2 text-sm font-medium">
                    <FileText class="h-4 w-4" />
                    <span>Attachments ({{ material.attachments.length }})</span>
                  </div>
                  <div class="space-y-2">
                    <a
                      v-for="attachment in material.attachments"
                      :key="attachment.id"
                      :href="route('student.materials.attachments.download', [classlist.id, material.id, attachment.id])"
                      rel="noopener noreferrer"
                      class="flex items-center justify-between rounded-lg border bg-card p-4 transition-all hover:bg-accent/50 hover:shadow-sm"
                    >
                      <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                          <Download class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                          <p class="text-sm font-medium truncate">{{ attachment.name }}</p>
                          <p class="text-xs text-muted-foreground">{{ formatFileSize(attachment.size) }}</p>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <Link
                          v-if="isPreviewable(attachment)"
                          :href="getPreviewUrl(attachment.id, attachment.name)"
                          target="_blank"
                          as="button"
                        >
                          <Button variant="outline" size="sm">
                            <Eye class="h-4 w-4 mr-1" />
                            Preview
                          </Button>
                        </Link>
                        <Button variant="ghost" size="sm">
                          <Download class="h-4 w-4" />
                        </Button>
                      </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Sidebar -->
          <aside class="md:col-span-4">
            <div class="space-y-4">
              <!-- Material Info Card -->
              <Card class="border-2">
                <CardHeader class="pb-3">
                  <CardTitle class="text-base">Material Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="space-y-3">
                    <div class="flex items-start gap-3">
                      <User class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Posted by</p>
                        <p class="text-sm font-medium">{{ material.author.name }}</p>
                      </div>
                    </div>
                    <div class="flex items-start gap-3">
                      <Clock class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Created</p>
                        <p class="text-sm font-medium">{{ formatDate(material.created_at) }}</p>
                      </div>
                    </div>
                    <div v-if="material.accessible_date" class="flex items-start gap-3">
                      <Calendar class="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                      <div class="min-w-0 flex-1">
                        <p class="text-xs text-muted-foreground">Accessible from</p>
                        <p class="text-sm font-medium">
                          {{ formatDate(material.accessible_date) }}
                          <span v-if="material.accessible_time"> at {{ formatTime(material.accessible_time) }}</span>
                        </p>
                      </div>
                    </div>
                    <div v-else class="flex items-start gap-3">
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
            :comments="material.comments || []"
            commentable-type="App\\Models\\Material"
            :commentable-id="material.id"
            :classlist-id="classlist.id"
          />
        </div>
      </main>
    </div>
  </AppLayout>
</template>
