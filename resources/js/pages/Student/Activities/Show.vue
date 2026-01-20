<script setup lang="ts">
import SubmissionEvaluation from '@/components/SubmissionEvaluation.vue';
import { Button } from '@/components/ui/button';
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Code2, FileText, Paperclip, Play } from 'lucide-vue-next';
import { computed } from 'vue';
import CommentSection from '@/components/CommentSection.vue';

const props = defineProps<{
    classlist: { id: string; name: string; room: string; academic_year: string };
    activity: {
        id: number;
        title: string;
        instruction: string | null;
        points: number | null;
        due_date: string | null;
        due_time: string | null;
        created_at: string;
        status?: 'Draft' | 'Submitted' | 'Graded' | 'Missing';
        attachments: Array<{ id: number; name: string; url: string; type: string | null }>;
        comments?: Array<{
            id: number;
            content: string;
            created_at: string;
            user: { id: number; name: string };
            replies?: Array<{
                id: number;
                content: string;
                created_at: string;
                user: { id: number; name: string };
            }>;
        }>;
    };
}>();

function formatDate(dateLike?: string | null) {
    if (!dateLike) return '';
    const d = new Date(dateLike);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}
function formatTime(value?: string | null) {
    if (!value) return '';
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        const [hh, mm] = value.split(':');
        const d = new Date();
        d.setHours(Number(hh), Number(mm), 0, 0);
        return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }
    const d = new Date(value);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}

// Navigate to coding workspace
const goStartCoding = () => {
    router.visit(route('student.activities.answer', [props.classlist.id, props.activity.id]));
};
const isStartDisabled = computed(() => ['Missing', 'Submitted', 'Graded'].includes(props.activity.status ?? 'draft'));
</script>

<template>
    <Head :title="`${props.activity.title} · ${props.classlist.name}`" />

    <AuthLayoutStudent>
        <!-- Full canvas with subtle gradient + sticky header -->
        <div class="min-h-[100dvh] bg-[linear-gradient(180deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.03)_100%)]">
            <!-- Header -->
            <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-3 sm:px-4 md:px-6 py-3 md:py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <Link :href="route('student.activities.index', props.classlist.id)" as="button" aria-label="Back to activities">
                            <Button variant="outline" size="sm"> <ArrowLeft class="mr-1 h-4 w-4" /> Back </Button>
                        </Link>
                        <div class="min-w-0">
                            <h1 class="truncate text-base font-semibold md:text-lg">
                                {{ props.activity.title }}
                            </h1>
                            <p class="truncate text-xs text-muted-foreground md:text-sm">
                                {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
                            </p>
                        </div>
                    </div>

                    <div class="hidden shrink-0 text-right text-xs text-muted-foreground md:block">
                        Posted: {{ formatDate(props.activity.created_at) }}
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="mx-auto w-full max-w-[1600px] px-3 sm:px-4 md:px-6 py-4 sm:py-6 md:py-8">
                <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-12">
                    <!-- Instructions / body -->
                    <section class="md:col-span-8">
                        <div class="rounded-2xl border bg-card shadow-sm">
                            <div class="border-b px-5 py-4 md:px-6">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <FileText class="h-4 w-4" />
                                    <span>Instructions</span>
                                </div>
                            </div>

                            <div class="px-5 py-5 md:px-6 md:py-6">
                                <div v-if="props.activity.instruction" class="prose prose-sm dark:prose-invert max-w-none">
                                    <p class="leading-7 whitespace-pre-line">
                                        {{ props.activity.instruction }}
                                    </p>
                                </div>
                                <div v-else class="text-sm text-muted-foreground">No instructions provided for this activity.</div>

                                <!-- Attachments -->
                                <div v-if="props.activity.attachments?.length" class="mt-7">
                                    <div class="mb-3 flex items-center gap-2 text-sm font-medium">
                                        <Paperclip class="h-4 w-4" />
                                        <span>Attachments</span>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <a
                                            v-for="f in props.activity.attachments"
                                            :key="f.id"
                                            :href="f.url"
                                            target="_blank"
                                            class="group inline-flex items-center justify-between rounded-xl border px-3.5 py-2.5 text-sm shadow-sm transition-colors hover:bg-accent"
                                        >
                                            <span class="truncate">{{ f.name }}</span>
                                            <span class="text-xs text-muted-foreground transition-colors group-hover:text-foreground">Open</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Sidebar: meta + CTA -->
                    <aside class="md:col-span-4">
                        <div class="sticky top-4 space-y-4">
                            <div class="rounded-2xl border bg-card p-5 shadow-sm md:p-6">
                                <div class="space-y-5">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Code2 class="h-4 w-4" />
                                        <span>
                                            <span class="font-medium text-foreground">{{ props.activity.points ?? '—' }}</span> pts
                                        </span>
                                    </div>

                                    <div class="flex items-start gap-2 text-sm text-muted-foreground">
                                        <Clock class="mt-0.5 h-4 w-4" />
                                        <div>
                                            <div class="font-medium text-foreground">Due</div>
                                            <div v-if="props.activity.due_date">
                                                {{ formatDate(props.activity.due_date) }}
                                                <span v-if="props.activity.due_time"> at {{ formatTime(props.activity.due_time) }}</span>
                                            </div>
                                            <div v-else class="text-xs text-muted-foreground">No due date</div>
                                        </div>
                                    </div>

                                    <div class="pt-1">
                                        <Button class="w-full" size="lg" @click="goStartCoding" :disabled="isStartDisabled">
                                            <Play class="mr-2 h-4 w-4" />
                                            Start Coding
                                        </Button>
                                    </div>

                                    <div v-if="props.activity.attachments?.length" class="flex gap-2">
                                        <Link
                                            :href="props.activity.attachments[0].url"
                                            target="_blank"
                                            as="button"
                                            class="flex-1"
                                            aria-label="Open first attachment"
                                        >
                                            <Button variant="outline" class="w-full">View Attachment</Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-card/60 p-4 text-xs text-muted-foreground shadow-sm">
                                Tip: You can revisit this page anytime. Your coding workspace autosaves drafts periodically.
                            </div>
                        </div>
                    </aside>
                </div>
            </main>

            <!-- Mobile sticky action bar -->
            <div class="fixed inset-x-0 bottom-0 z-20 border-t bg-background/95 p-3 backdrop-blur md:hidden">
                <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-3 px-1">
                    <div class="min-w-0 text-xs text-muted-foreground">
                        <div class="truncate">
                            <span v-if="props.activity.due_date">
                                Due: {{ formatDate(props.activity.due_date) }}
                                <span v-if="props.activity.due_time"> at {{ formatTime(props.activity.due_time) }}</span>
                            </span>
                            <span v-else>No due date</span>
                        </div>
                    </div>
                    <Button size="sm" @click="goStartCoding" aria-label="Start coding now">
                        <Play class="mr-2 h-4 w-4" />
                        Start
                    </Button>
                </div>
            </div>
            <div class="mx-auto mt-8 w-full max-w-[1600px] px-4 py-6 md:py-8">
                <SubmissionEvaluation
                    :fetch-url="route('student.activities.evaluation', [props.classlist.id, props.activity.id])"
                    :points="props.activity.points ?? 0"
                />
            </div>

            <!-- Comments Section -->
            <div class="mx-auto mt-6 w-full max-w-[1600px] px-4">
                <CommentSection
                    :comments="props.activity.comments || []"
                    commentable-type="App\\Models\\Activity"
                    :commentable-id="props.activity.id"
                    :classlist-id="props.classlist.id"
                />
            </div>
        </div>
    </AuthLayoutStudent>
</template>
