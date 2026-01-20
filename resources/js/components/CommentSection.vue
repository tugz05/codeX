<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Badge } from '@/components/ui/badge'
import { MessageSquare, Send, Edit2, Trash2, Reply, X, Loader2, Lock, Globe } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { EllipsisVertical } from 'lucide-vue-next'

interface Comment {
  id: number
  content: string
  visibility?: 'public' | 'private'
  created_at: string
  user: {
    id: number
    name: string
  }
  replies?: Comment[]
}

interface Props {
  comments: Comment[]
  commentableType: 'App\\Models\\Activity' | 'App\\Models\\Assignment' | 'App\\Models\\Material' | 'App\\Models\\ActivitySubmission' | 'App\\Models\\AssignmentSubmission'
  commentableId: number
  classlistId?: string
}

const props = defineProps<Props>()

const page = usePage()
const currentUser = computed(() => (page.props as any).auth?.user)

const showReplyForm = ref<number | null>(null)
const editingCommentId = ref<number | null>(null)
const replyToCommentId = ref<number | null>(null)

const isStudent = computed(() => currentUser.value?.account_type === 'student')

const commentForm = useForm({
  content: '',
  commentable_type: props.commentableType,
  commentable_id: props.commentableId,
  parent_id: null as number | null,
  classlist_id: props.classlistId || null,
  visibility: 'public' as 'public' | 'private',
})

const replyForm = useForm({
  content: '',
  commentable_type: props.commentableType,
  commentable_id: props.commentableId,
  parent_id: null as number | null,
  classlist_id: props.classlistId || null,
  visibility: 'public' as 'public' | 'private',
})

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`
  if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`
  if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const routePrefix = computed(() => {
  const user = currentUser.value
  return user?.account_type === 'instructor' ? 'instructor' : 'student'
})

function submitComment() {
  if (!commentForm.content.trim()) {
    return
  }
  
  commentForm.post(route(`${routePrefix.value}.comments.store`), {
    preserveScroll: true,
    onSuccess: () => {
      commentForm.reset('content')
      // Reload the current page to fetch updated comments
      router.reload({ preserveScroll: true })
    },
    onError: (errors) => {
      console.error('Comment submission errors:', errors)
    },
  })
}

function startReply(commentId: number) {
  replyToCommentId.value = commentId
  replyForm.parent_id = commentId
  showReplyForm.value = commentId
}

function cancelReply() {
  replyToCommentId.value = null
  replyForm.reset('content', 'parent_id', 'visibility')
  replyForm.visibility = 'public'
  showReplyForm.value = null
}

function submitReply() {
  if (!replyForm.content.trim()) {
    return
  }
  
  replyForm.post(route(`${routePrefix.value}.comments.store`), {
    preserveScroll: true,
    onSuccess: () => {
      cancelReply()
      // Reload the current page to fetch updated comments
      router.reload({ preserveScroll: true })
    },
    onError: (errors) => {
      console.error('Reply submission errors:', errors)
    },
  })
}

function startEdit(comment: Comment) {
  editingCommentId.value = comment.id
  commentForm.content = comment.content
  commentForm.parent_id = comment.id
}

function cancelEdit() {
  editingCommentId.value = null
  commentForm.reset('content', 'parent_id')
}

function updateComment(commentId: number) {
  commentForm.put(route(`${routePrefix.value}.comments.update`, commentId), {
    preserveScroll: true,
    onSuccess: () => {
      cancelEdit()
      // Reload the current page to fetch updated comments
      router.reload({ preserveScroll: true })
    },
  })
}

function deleteComment(commentId: number) {
  if (confirm('Are you sure you want to delete this comment?')) {
    router.delete(route(`${routePrefix.value}.comments.destroy`, commentId), {
      preserveScroll: true,
      onSuccess: () => {
        // Reload the current page to fetch updated comments
        router.reload({ preserveScroll: true })
      },
    })
  }
}

function canEdit(comment: Comment): boolean {
  return currentUser.value?.id === comment.user.id
}
</script>

<template>
  <Card class="mt-6 border-2">
    <CardHeader class="pb-3">
      <CardTitle class="text-base flex items-center gap-2">
        <MessageSquare class="h-4 w-4" />
        Comments ({{ comments.length }})
      </CardTitle>
    </CardHeader>
    <CardContent class="space-y-6">
      <!-- Add Comment Form -->
      <form @submit.prevent="submitComment" class="space-y-3">
        <Textarea
          v-model="commentForm.content"
          placeholder="Add a comment or ask a question..."
          class="min-h-[100px] border-2 transition-all duration-300 focus:scale-[1.01]"
          :disabled="commentForm.processing"
          required
        />
        
        <!-- Visibility Selector (Students Only) -->
        <div v-if="isStudent" class="space-y-2">
          <Label class="text-sm font-medium">Visibility</Label>
          <RadioGroup v-model="commentForm.visibility" class="flex gap-4">
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="public" id="visibility-public" />
              <Label for="visibility-public" class="flex items-center gap-2 cursor-pointer">
                <Globe class="h-4 w-4 text-muted-foreground" />
                <span>Public (Everyone can see)</span>
              </Label>
            </div>
            <div class="flex items-center space-x-2">
              <RadioGroupItem value="private" id="visibility-private" />
              <Label for="visibility-private" class="flex items-center gap-2 cursor-pointer">
                <Lock class="h-4 w-4 text-muted-foreground" />
                <span>Private (Instructor only)</span>
              </Label>
            </div>
          </RadioGroup>
        </div>
        
        <Alert v-if="commentForm.errors.content" variant="destructive">
          <AlertDescription>{{ commentForm.errors.content }}</AlertDescription>
        </Alert>
        <Alert v-if="commentForm.errors.visibility" variant="destructive">
          <AlertDescription>{{ commentForm.errors.visibility }}</AlertDescription>
        </Alert>
        <Alert v-if="Object.keys(commentForm.errors).length > 0 && !commentForm.errors.content && !commentForm.errors.visibility" variant="destructive">
          <AlertDescription>
            <div v-for="(error, key) in commentForm.errors" :key="key">
              {{ error }}
            </div>
          </AlertDescription>
        </Alert>
        <div class="flex justify-end">
          <Button
            type="submit"
            :disabled="commentForm.processing || !commentForm.content.trim()"
            size="sm"
            class="transition-all duration-200 hover:scale-105"
          >
            <Loader2 v-if="commentForm.processing" class="mr-2 h-4 w-4 animate-spin" />
            <Send v-else class="mr-2 h-4 w-4" />
            {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
          </Button>
        </div>
      </form>

      <!-- Comments List -->
      <div v-if="comments.length > 0" class="space-y-4">
        <div
          v-for="comment in comments"
          :key="comment.id"
          class="rounded-lg border bg-card p-4 space-y-3"
        >
          <!-- Comment Header -->
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold text-sm">{{ comment.user.name }}</span>
                <span class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                <Badge v-if="comment.visibility === 'private'" variant="secondary" class="text-xs flex items-center gap-1">
                  <Lock class="h-3 w-3" />
                  Private
                </Badge>
                <Badge v-else-if="comment.visibility === 'public'" variant="outline" class="text-xs flex items-center gap-1">
                  <Globe class="h-3 w-3" />
                  Public
                </Badge>
              </div>
            </div>
            <DropdownMenu v-if="canEdit(comment)">
              <DropdownMenuTrigger as-child>
                <button class="text-muted-foreground hover:text-foreground transition-colors">
                  <EllipsisVertical class="h-4 w-4" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem @click="startEdit(comment)">
                  <Edit2 class="mr-2 h-4 w-4" />
                  Edit
                </DropdownMenuItem>
                <DropdownMenuItem @click="deleteComment(comment.id)" class="text-destructive">
                  <Trash2 class="mr-2 h-4 w-4" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <!-- Comment Content -->
          <div v-if="editingCommentId !== comment.id" class="text-sm text-foreground whitespace-pre-wrap">
            {{ comment.content }}
          </div>

          <!-- Edit Form -->
          <div v-else class="space-y-2">
            <Textarea
              v-model="commentForm.content"
              class="min-h-[80px] border-2"
              :disabled="commentForm.processing"
            />
            <div class="flex justify-end gap-2">
              <Button variant="outline" size="sm" @click="cancelEdit" :disabled="commentForm.processing">
                Cancel
              </Button>
              <Button size="sm" @click="updateComment(comment.id)" :disabled="commentForm.processing || !commentForm.content.trim()">
                <Loader2 v-if="commentForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                Save
              </Button>
            </div>
          </div>

          <!-- Reply Button -->
          <div class="flex items-center gap-2">
            <Button
              variant="ghost"
              size="sm"
              @click="startReply(comment.id)"
              class="text-xs"
            >
              <Reply class="mr-1 h-3 w-3" />
              Reply
            </Button>
          </div>

          <!-- Reply Form -->
          <div v-if="showReplyForm === comment.id" class="ml-6 mt-3 space-y-2 border-l-2 pl-4">
            <Textarea
              v-model="replyForm.content"
              placeholder="Write a reply..."
              class="min-h-[80px] border-2"
              :disabled="replyForm.processing"
            />
            
            <!-- Visibility Selector for Replies (Students Only) -->
            <div v-if="isStudent" class="space-y-2">
              <Label class="text-sm font-medium">Visibility</Label>
              <RadioGroup v-model="replyForm.visibility" class="flex gap-4">
                <div class="flex items-center space-x-2">
                  <RadioGroupItem value="public" id="reply-visibility-public" />
                  <Label for="reply-visibility-public" class="flex items-center gap-2 cursor-pointer">
                    <Globe class="h-4 w-4 text-muted-foreground" />
                    <span>Public</span>
                  </Label>
                </div>
                <div class="flex items-center space-x-2">
                  <RadioGroupItem value="private" id="reply-visibility-private" />
                  <Label for="reply-visibility-private" class="flex items-center gap-2 cursor-pointer">
                    <Lock class="h-4 w-4 text-muted-foreground" />
                    <span>Private</span>
                  </Label>
                </div>
              </RadioGroup>
            </div>
            
            <Alert v-if="replyForm.errors.content" variant="destructive">
              <AlertDescription>{{ replyForm.errors.content }}</AlertDescription>
            </Alert>
            <Alert v-if="replyForm.errors.visibility" variant="destructive">
              <AlertDescription>{{ replyForm.errors.visibility }}</AlertDescription>
            </Alert>
            <div class="flex justify-end gap-2">
              <Button variant="outline" size="sm" @click="cancelReply" :disabled="replyForm.processing">
                <X class="mr-1 h-3 w-3" />
                Cancel
              </Button>
              <Button size="sm" @click="submitReply" :disabled="replyForm.processing || !replyForm.content.trim()">
                <Loader2 v-if="replyForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                <Send v-else class="mr-2 h-4 w-4" />
                {{ replyForm.processing ? 'Posting...' : 'Reply' }}
              </Button>
            </div>
          </div>

          <!-- Replies -->
          <div v-if="comment.replies && comment.replies.length > 0" class="ml-6 mt-3 space-y-3 border-l-2 pl-4">
            <div
              v-for="reply in comment.replies"
              :key="reply.id"
              class="rounded-lg border bg-muted/30 p-3"
            >
              <div class="flex items-start justify-between mb-1">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm">{{ reply.user.name }}</span>
                  <span class="text-xs text-muted-foreground">{{ formatDate(reply.created_at) }}</span>
                </div>
                <DropdownMenu v-if="canEdit(reply)">
                  <DropdownMenuTrigger as-child>
                    <button class="text-muted-foreground hover:text-foreground transition-colors">
                      <EllipsisVertical class="h-4 w-4" />
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="deleteComment(reply.id)" class="text-destructive">
                      <Trash2 class="mr-2 h-4 w-4" />
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
              <div class="text-sm text-foreground whitespace-pre-wrap">{{ reply.content }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8 text-muted-foreground">
        <MessageSquare class="h-12 w-12 mx-auto mb-3 opacity-50" />
        <p class="text-sm">No comments yet. Be the first to comment!</p>
      </div>
    </CardContent>
  </Card>
</template>
