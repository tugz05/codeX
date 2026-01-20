<!-- resources/js/components/SubmissionEvaluation.vue -->
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const props = defineProps<{
  fetchUrl: string // route('student.activities.evaluation', [classlistId, activityId])
  points: number
}>()

const loading = ref(true)
const evaluation = ref<null | {
  score: number | null
  feedback: string | null
  criteria_breakdown: Array<{
    id:string|null,
    name:string,
    weight:number,
    points:number,
    score:number,
    percentage:number,
    comment:string
  }>
}>(null)

const error = ref<string | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await fetch(props.fetchUrl, { headers: { 'Accept':'application/json' } })
    const data = await res.json()
    if (data.ok) {
      evaluation.value = data.evaluation
    } else {
      error.value = 'Failed to load evaluation.'
    }
  } catch (e:any) {
    error.value = e.message || 'Failed to load evaluation.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
    <div class="mb-2 text-sm font-medium">AI Evaluation</div>

    <div v-if="loading" class="text-sm text-muted-foreground">Loading…</div>

    <Alert v-else-if="error" variant="destructive">
      <AlertTitle>Error</AlertTitle>
      <AlertDescription>{{ error }}</AlertDescription>
    </Alert>

    <div v-else-if="!evaluation" class="text-sm text-muted-foreground">
      Not graded yet. Submit and run AI grading to see results.
    </div>

    <div v-else>
      <div class="mb-3 flex items-center justify-between text-sm">
        <div>
          Overall Score: <span class="font-semibold">{{ evaluation.score ?? '—' }}</span> / {{ points }}
        </div>
      </div>

      <div class="overflow-hidden rounded-md border">
        <table class="w-full text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-3 py-2 text-left">Criterion</th>
              <th class="px-3 py-2 text-left">Weight</th>
              <th class="px-3 py-2 text-left">Points</th>
              <th class="px-3 py-2 text-left">Score</th>
              <th class="px-3 py-2 text-left">%</th>
              <th class="px-3 py-2 text-left">Comment</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in evaluation.criteria_breakdown" :key="c.id ?? c.name" class="border-t">
              <td class="px-3 py-2">{{ c.name }}</td>
              <td class="px-3 py-2">{{ c.weight }}%</td>
              <td class="px-3 py-2">{{ c.points }}</td>
              <td class="px-3 py-2 font-semibold">{{ c.score }}</td>
              <td class="px-3 py-2">{{ c.percentage }}%</td>
              <td class="px-3 py-2 text-muted-foreground">{{ c.comment }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <Separator class="my-3" />

      <div v-if="evaluation.feedback" class="text-sm">
        <div class="mb-1 font-medium">Feedback</div>
        <p class="text-muted-foreground whitespace-pre-line">{{ evaluation.feedback }}</p>
      </div>
    </div>
  </div>
</template>
