<template>
    <Head :title="`Submissions · ${classlist.name}`" />

    <AuthLayoutStudent>
        <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
            <!-- Header -->
            <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-4 py-3 md:py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Link :href="route('student.activities.index', classlist.id)" as="button" aria-label="Back to activities">
                            <Button variant="outline" size="sm"> <ArrowLeft class="mr-1 h-4 w-4" /> Back </Button>
                        </Link>
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-semibold md:text-lg">
                                Submission History
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
                <div class="rounded-xl border bg-card shadow-sm">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">Activity</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Score</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Submitted</th>
                                <th class="w-[1%] px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sub in submissions" :key="sub.id" class="border-b last:border-none">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium">{{ sub.activity.title }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        Due: {{ formatDate(sub.activity.due_date) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="getStatusVariant(sub.status)">{{ sub.status }}</Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="sub.score !== null" class="text-sm">
                                        {{ sub.score }} / {{ sub.activity.points }}
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">—</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">{{ formatDate(sub.submitted_at) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ formatTime(sub.submitted_at) }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('student.submissions.show', [classlist.id, sub.activity.id, sub.id])"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >
                                        Details
                                        <ChevronRight class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!submissions.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No submissions yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
import { ArrowLeft, ChevronRight } from 'lucide-vue-next'

interface Props {
    classlist: {
        id: string
        name: string
        room: string
        academic_year: string
    }
    submissions: Array<{
        id: number
        activity: {
            id: number
            title: string
            points: number
            due_date: string | null
        }
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
    }>
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
