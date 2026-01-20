<template>
    <Head :title="`${activity.title} Submission · ${classlist.name}`" />

    <AuthLayoutStudent>
        <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
            <!-- Header -->
            <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-4 py-3 md:py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Link :href="route('student.submissions.index', classlist.id)" as="button" aria-label="Back to submissions">
                            <Button variant="outline" size="sm"> <ArrowLeft class="mr-1 h-4 w-4" /> Back </Button>
                        </Link>
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-semibold md:text-lg">
                                {{ activity.title }} - Submission
                            </h1>
                            <p class="truncate text-xs text-muted-foreground md:text-sm">
                                {{ classlist.name }} • AY {{ classlist.academic_year }} • Room {{ classlist.room }}
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="mx-auto w-full max-w-[1600px] px-4 py-6 md:py-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                    <!-- Submission details -->
                    <div class="md:col-span-8">
                        <div class="space-y-6">
                            <!-- Code submission -->
                            <div class="rounded-xl border bg-card shadow-sm">
                                <div class="border-b px-5 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Code class="h-4 w-4" />
                                            <span>Submitted Code</span>
                                        </div>
                                        <Badge :variant="getStatusVariant(submission.status)">
                                            {{ submission.status }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <pre class="p-4"><code>{{ submission.code }}</code></pre>
                                </div>
                            </div>

                            <!-- Evaluation -->
                            <div v-if="submission.evaluation" class="rounded-xl border bg-card shadow-sm">
                                <div class="border-b px-5 py-4">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <CheckCircle class="h-4 w-4" />
                                        <span>Evaluation Results</span>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <!-- Score -->
                                    <div class="mb-6 flex items-center justify-between">
                                        <div class="text-lg font-semibold">
                                            Score: {{ submission.score }} / {{ activity.points }}
                                        </div>
                                    </div>

                                    <!-- Criteria breakdown -->
                                    <div class="overflow-hidden rounded-lg border">
                                        <table class="w-full text-sm">
                                            <thead class="bg-muted/50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left">Criterion</th>
                                                    <th class="px-4 py-2 text-left">Weight</th>
                                                    <th class="px-4 py-2 text-left">Points</th>
                                                    <th class="px-4 py-2 text-left">Score</th>
                                                    <th class="px-4 py-2 text-left">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="c in submission.evaluation.criteria_breakdown" :key="c.name" class="border-t">
                                                    <td class="px-4 py-2">{{ c.name }}</td>
                                                    <td class="px-4 py-2">{{ c.weight }}%</td>
                                                    <td class="px-4 py-2">{{ c.points }}</td>
                                                    <td class="px-4 py-2 font-medium">{{ c.score }}</td>
                                                    <td class="px-4 py-2">{{ c.percentage }}%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Feedback -->
                                    <div v-if="submission.evaluation.feedback" class="mt-6">
                                        <div class="mb-2 text-sm font-medium">Feedback</div>
                                        <div class="whitespace-pre-line rounded-lg border bg-muted/30 p-4 text-sm">
                                            {{ submission.evaluation.feedback }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="md:col-span-4">
                        <div class="sticky top-24 space-y-6">
                            <!-- Meta info -->
                            <div class="rounded-xl border bg-card p-5 shadow-sm">
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium">Submitted on</dt>
                                        <dd class="mt-1 text-sm text-muted-foreground">
                                            {{ formatDate(submission.submitted_at) }}
                                            <div class="text-xs">{{ formatTime(submission.submitted_at) }}</div>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium">Language</dt>
                                        <dd class="mt-1 text-sm text-muted-foreground">
                                            {{ submission.language }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium">Due date</dt>
                                        <dd class="mt-1 text-sm text-muted-foreground">
                                            {{ formatDate(activity.due_date) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AuthLayoutStudent>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Code, CheckCircle } from 'lucide-vue-next'

interface Props {
    classlist: {
        id: string
        name: string
        room: string
        academic_year: string
    }
    activity: {
        id: number
        title: string
        points: number
        due_date: string | null
    }
    submission: {
        id: number
        code: string
        language: string
        submitted_at: string
        status: 'draft' | 'submitted' | 'graded'
        score: number | null
        evaluation: {
            feedback: string | null
            criteria_breakdown: Array<{
                name: string
                weight: number
                points: number
                score: number
                percentage: number
                comment: string
            }>
        } | null
    }
}
const props = defineProps<Props>()

function formatDate(dateLike?: string | null) {
    if (!dateLike) return '—'
    return new Date(dateLike).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

function formatTime(dateLike?: string | null) {
    if (!dateLike) return ''
    return new Date(dateLike).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    })
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'draft': return 'secondary'
        case 'submitted': return 'default'
        case 'graded': return 'success'
        default: return 'secondary'
    }
}
</script>
