<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Link } from '@inertiajs/vue3'
import { EllipsisVertical } from 'lucide-vue-next'

const props = defineProps<{
  activity: {
    id: number
    title: string
    instruction: string | null
    points: number | null
    due_date: string | null
    due_time: string | null
    accessible_date: string | null
    accessible_time: string | null
    created_at: string
    attachments: Array<{ id:number; name:string; url:string; type:string | null }>
  }
  classlistId?: string
}>()

const emit = defineEmits<{
  (e:'edit', activity: typeof props.activity): void
  (e:'delete', id: number): void
}>()

function formatDate(value?: string | null) {
  if (!value) return ''
  const d = new Date(value) // works for ISO like 2025-08-12T16:06:00.000000Z and YYYY-MM-DD
  if (isNaN(d.getTime())) return ''
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function formatTime(value?: string | null) {
  if (!value) return ''
  // If it's already "HH:mm" (or "HH:mm:ss"), build a Date for today
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
    const [hh, mm] = value.split(':')
    const d = new Date()
    d.setHours(Number(hh), Number(mm), 0, 0)
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
  }
  // Otherwise assume ISO datetime
  const d = new Date(value)
  if (isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

function isActivityAccessible(): boolean {
  if (!props.activity.accessible_date) return true
  
  const now = new Date()
  const accessibleDate = new Date(props.activity.accessible_date)
  
  if (props.activity.accessible_time) {
    const [hours, minutes] = props.activity.accessible_time.split(':')
    accessibleDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    accessibleDate.setHours(0, 0, 0, 0)
  }
  
  return now >= accessibleDate
}

function isPastDue(): boolean {
  if (!props.activity.due_date) return false
  
  const now = new Date()
  const dueDate = new Date(props.activity.due_date)
  
  if (props.activity.due_time) {
    const [hours, minutes] = props.activity.due_time.split(':')
    dueDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  } else {
    dueDate.setHours(23, 59, 59, 999) // End of day if no time specified
  }
  
  return now > dueDate
}

</script>

<template>
  <div class="w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900 p-4 hover:shadow-md transition-shadow cursor-pointer">
    <div class="flex items-start justify-between">
      <div 
        class="space-y-1 flex-1 min-w-0"
        @click="emit('edit', activity)"
      >
        <div class="flex items-center gap-2 mb-2">
          <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
            Activity
          </span>
          <h3 class="text-base font-semibold">{{ activity.title }}</h3>
          <span
            v-if="!activity.accessible_date"
            class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
          >
            Available
          </span>
          <span
            v-else-if="isActivityAccessible()"
            class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
          >
            Available Now
          </span>
          <span
            v-else
            class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
          >
            Scheduled
          </span>
          <span
            v-if="isPastDue()"
            class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300"
          >
            Past Due
          </span>
        </div>
        <p v-if="activity.instruction" class="text-sm text-muted-foreground line-clamp-3">
          {{ activity.instruction }}
        </p>
        <div class="text-xs text-muted-foreground flex flex-wrap gap-3">
          <span>Created: {{ new Date(activity.created_at).toLocaleDateString() }}</span>
          <span v-if="activity.points !== null">• {{ activity.points }} pts</span>
          <span v-if="activity.due_date">• Due: {{ formatDate(props.activity.due_date) }} <span v-if="activity.due_time">{{ formatTime(props.activity.due_time) }}</span></span>
        </div>
      </div>

      <!-- Ellipsis menu -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button 
            class="text-gray-500 transition-colors hover:text-gray-900 dark:text-neutral-400 dark:hover:text-white"
            @click.stop
          >
            <EllipsisVertical class="h-5 w-5" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
          <DropdownMenuItem @click="emit('edit', activity)">Edit</DropdownMenuItem>
          <DropdownMenuItem @click="emit('delete', activity.id)">
            <span class="text-red-500">Delete</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <div v-if="activity.attachments?.length" class="mt-3 flex flex-wrap gap-2">
      <a v-for="f in activity.attachments" :key="f.id" :href="f.url" target="_blank"
         class="inline-flex items-center rounded-md border px-2 py-1 text-xs hover:bg-accent">
        {{ f.name }}
      </a>
    </div>
  </div>
</template>
