<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Edit, Download, Calendar, User, NotebookPen, FileText, Clock, CheckSquare } from 'lucide-vue-next'
import CommentSection from '@/components/CommentSection.vue'

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
}>()

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
    <Head :title="`${assignment.title} · ${classlist.name}`" />

    <!-- Full canvas with subtle gradient + sticky header -->
    <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
      <!-- Header -->
      <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-3 sm:px-4 md:px-6 py-3 md:py-4">
          <div class="flex min-w-0 items-center gap-3">
            <Link :href="route('instructor.activities.index', classlist.id)" as="button" aria-label="Back to activities">
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

          <div class="flex items-center gap-3">
            <div class="hidden shrink-0 text-right text-xs text-muted-foreground md:block">
              Posted: {{ formatDate(assignment.created_at) }}
            </div>
            <Link :href="route('instructor.assignments.grading', [classlist.id, assignment.id])">
              <Button variant="outline">
                <CheckSquare class="mr-2 h-4 w-4" />
                Grade Submissions
              </Button>
            </Link>
            <Link :href="route('instructor.assignments.edit', [classlist.id, assignment.id])">
              <Button>
                <Edit class="mr-2 h-4 w-4" />
                Edit Assignment
              </Button>
            </Link>
          </div>
        </div>
      </header>

      <!-- Main content -->
      <main class="mx-auto w-full max-w-[1200px] px-4 py-6 md:py-8">
        <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-12 max-w-[1600px] mx-auto w-full px-3 sm:px-4 md:px-6">
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
                  <div class="leading-7 whitespace-pre-line text-foreground" v-html="assignment.instruction"></div>
                </div>
                <div v-else class="text-sm text-muted-foreground">No instructions provided for this assignment.</div>

                <!-- Attachments -->
                <div v-if="assignment.attachments.length > 0" class="mt-6 space-y-3">
                  <div class="flex items-center gap-2 text-sm font-medium">
                    <FileText class="h-4 w-4" />
                    <span>Attachments ({{ assignment.attachments.length }})</span>
                  </div>
                  <div class="space-y-2">
                    <a
                      v-for="attachment in assignment.attachments"
                      :key="attachment.id"
                      :href="attachment.url"
                      target="_blank"
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
                      <Button variant="ghost" size="sm" class="shrink-0">
                        <Download class="h-4 w-4" />
                      </Button>
                    </a>
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

              <!-- Quick Actions Card -->
              <Card class="border-2">
                <CardHeader class="pb-3">
                  <CardTitle class="text-base">Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-2">
                    <Link :href="route('instructor.assignments.edit', [classlist.id, assignment.id])" class="block">
                      <Button variant="outline" class="w-full justify-start">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit Assignment
                      </Button>
                    </Link>
                    <Link :href="route('instructor.activities.index', classlist.id)" class="block">
                      <Button variant="ghost" class="w-full justify-start">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Content
                      </Button>
                    </Link>
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
