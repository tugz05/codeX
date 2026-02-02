<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Check, X, FileText, Link as LinkIcon, Video, Download, MessageSquare, Star, AlertCircle, Eye } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import FilePreviewEmbed from '@/components/FilePreviewEmbed.vue';

interface Props {
  classlist: {
    id: string;
    name: string;
    academic_year: string;
  };
  assignment: {
    id: number;
    title: string;
    points: number | null;
    due_date: string | null;
    due_time: string | null;
  };
  submissions: Array<{
    id: number;
    student: { id: number; name: string; email: string };
    submission_type: string;
    link_url: string | null;
    video_url: string | null;
    attachments: Array<{ id: number; name: string; url: string; size: number }>;
    status: string;
    score: number | null;
    feedback: string | null;
    annotations: Array<any>;
    criteria_id: number | null;
    rubric_scores: Record<string, number>;
    grade_override: boolean;
    override_reason: string | null;
    returned_to_student: boolean;
    returned_at: string | null;
    submitted_at: string | null;
    created_at: string;
  }>;
  rubrics: Array<{
    id: number;
    title: string;
    description: string | null;
    grading_method: string;
    items: Array<{
      id: number;
      label: string;
      description: string | null;
      points: number;
      weight: number | null;
      sort_order: number;
    }>;
  }>;
}

const props = defineProps<Props>();

const selectedSubmission = ref<number | null>(null);
const selectedRubric = ref<number | null>(null);
const bulkGradingMode = ref(false);
const selectedForBulk = ref<Set<number>>(new Set());
const returnToStudents = ref(false);
const previewFile = ref<{ id: number; name: string; url: string; type?: string | null; size?: number | null } | null>(null);

const currentSubmission = computed(() => {
  if (!selectedSubmission.value) return null;
  return props.submissions.find(s => s.id === selectedSubmission.value);
});

const currentRubric = computed(() => {
  if (!selectedRubric.value) return null;
  return props.rubrics.find(r => r.id === selectedRubric.value);
});

const individualForm = useForm({
  score: 0,
  feedback: '',
  criteria_id: null as number | null,
  rubric_scores: {} as Record<string, number>,
  grade_override: false,
  override_reason: '',
  return_to_student: false,
});

const bulkForm = useForm({
  submissions: [] as Array<{ id: number; score: number; feedback: string }>,
  return_to_students: false,
});

const openSubmission = (submissionId: number) => {
  selectedSubmission.value = submissionId;
  const submission = props.submissions.find(s => s.id === submissionId);
  if (submission) {
    individualForm.score = submission.score ?? 0;
    individualForm.feedback = submission.feedback ?? '';
    individualForm.criteria_id = submission.criteria_id;
    individualForm.rubric_scores = submission.rubric_scores ?? {};
    individualForm.grade_override = submission.grade_override;
    individualForm.override_reason = submission.override_reason ?? '';
    individualForm.return_to_student = false;
    selectedRubric.value = submission.criteria_id;
    
    // Auto-preview first attachment if available
    if (submission.attachments && submission.attachments.length > 0) {
      previewFile.value = {
        id: submission.attachments[0].id,
        name: submission.attachments[0].name,
        url: submission.attachments[0].url,
        size: submission.attachments[0].size,
      };
    } else {
      previewFile.value = null;
    }
  }
};

const setPreviewFile = (att: { id: number; name: string; url: string; size: number }) => {
  previewFile.value = {
    id: att.id,
    name: att.name,
    url: att.url,
    size: att.size,
  };
};

const calculateRubricScore = () => {
  if (!currentRubric.value || !individualForm.rubric_scores) return 0;
  
  let total = 0;
  currentRubric.value.items.forEach(item => {
    const score = individualForm.rubric_scores[item.id] ?? 0;
    const weight = item.weight ?? 1;
    total += (score / item.points) * item.points * weight;
  });
  
  return Math.round(total);
};

const applyRubricScore = () => {
  if (currentRubric.value) {
    const calculatedScore = calculateRubricScore();
    individualForm.score = Math.min(calculatedScore, props.assignment.points ?? 999999);
  }
};

watch(() => individualForm.rubric_scores, () => {
  if (currentRubric.value && Object.keys(individualForm.rubric_scores).length > 0) {
    applyRubricScore();
  }
}, { deep: true });

const submitGrade = () => {
  if (!selectedSubmission.value) return;
  
  individualForm.post(route('instructor.assignments.submissions.grade', [
    props.classlist.id,
    props.assignment.id,
    selectedSubmission.value
  ]), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Submission graded successfully.');
      router.reload({ preserveScroll: true });
    },
    onError: () => {
      toast.error('Failed to grade submission.');
    },
  });
};

const toggleBulkSelection = (submissionId: number) => {
  if (selectedForBulk.has(submissionId)) {
    selectedForBulk.delete(submissionId);
  } else {
    selectedForBulk.add(submissionId);
  }
  updateBulkForm();
};

const updateBulkForm = () => {
  bulkForm.submissions = Array.from(selectedForBulk).map(id => {
    const submission = props.submissions.find(s => s.id === id);
    const existing = bulkForm.submissions.find(s => s.id === id);
    return {
      id,
      score: existing?.score ?? submission?.score ?? 0,
      feedback: existing?.feedback ?? submission?.feedback ?? '',
    };
  });
};

const getBulkScore = (submissionId: number) => {
  const item = bulkForm.submissions.find(s => s.id === submissionId);
  return item?.score ?? 0;
};

const setBulkScore = (submissionId: number, value: number) => {
  const item = bulkForm.submissions.find(s => s.id === submissionId);
  if (item) {
    item.score = value;
  } else {
    bulkForm.submissions.push({ id: submissionId, score: value, feedback: '' });
  }
};

const getBulkFeedback = (submissionId: number) => {
  const item = bulkForm.submissions.find(s => s.id === submissionId);
  return item?.feedback ?? '';
};

const setBulkFeedback = (submissionId: number, value: string) => {
  const item = bulkForm.submissions.find(s => s.id === submissionId);
  if (item) {
    item.feedback = value;
  } else {
    bulkForm.submissions.push({ id: submissionId, score: 0, feedback: value });
  }
};

const submitBulkGrade = () => {
  if (bulkForm.submissions.length === 0) {
    toast.error('Please select at least one submission.');
    return;
  }
  
  bulkForm.return_to_students = returnToStudents.value;
  bulkForm.post(route('instructor.assignments.bulk-grade', [
    props.classlist.id,
    props.assignment.id
  ]), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`${bulkForm.submissions.length} submissions graded successfully.`);
      selectedForBulk.clear();
      bulkGradingMode.value = false;
      router.reload({ preserveScroll: true });
    },
    onError: () => {
      toast.error('Failed to grade submissions.');
    },
  });
};

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'graded':
      return { label: 'Graded', variant: 'default' as const };
    case 'submitted':
      return { label: 'Submitted', variant: 'secondary' as const };
    default:
      return { label: 'Draft', variant: 'outline' as const };
  }
};

const pendingSubmissions = computed(() => props.submissions.filter(s => s.status === 'submitted'));
const gradedSubmissions = computed(() => props.submissions.filter(s => s.status === 'graded'));
</script>

<template>
  <Head :title="`Grade ${assignment.title}`" />
  <AppLayout>
    <div class="container mx-auto p-6 max-w-7xl">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <Button variant="outline" size="sm" @click="router.visit(route('instructor.assignments.show', [classlist.id, assignment.id]))">
              <ArrowLeft class="h-4 w-4 mr-1" /> Back
            </Button>
            <div>
              <h1 class="text-2xl font-bold">Grade Assignment</h1>
              <p class="text-muted-foreground">{{ assignment.title }} • {{ classlist.name }}</p>
            </div>
          </div>
          <Badge variant="outline" class="text-sm">
            {{ pendingSubmissions.length }} Pending • {{ gradedSubmissions.length }} Graded
          </Badge>
        </div>
      </div>

      <Tabs default-value="individual" class="space-y-4">
        <TabsList>
          <TabsTrigger value="individual">Individual Grading</TabsTrigger>
          <TabsTrigger value="bulk">Bulk Grading</TabsTrigger>
        </TabsList>

        <!-- Individual Grading Tab -->
        <TabsContent value="individual" class="space-y-4">
          <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <!-- Submissions List -->
            <Card class="lg:col-span-1">
              <CardHeader>
                <CardTitle>Submissions</CardTitle>
                <CardDescription>{{ submissions.length }} total</CardDescription>
              </CardHeader>
              <CardContent>
                <div class="space-y-2 max-h-[700px] overflow-y-auto">
                  <div
                    v-for="submission in submissions"
                    :key="submission.id"
                    @click="openSubmission(submission.id)"
                    :class="[
                      'p-3 border rounded-lg cursor-pointer transition-colors',
                      selectedSubmission === submission.id ? 'bg-primary/10 border-primary' : 'hover:bg-accent'
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ submission.student.name }}</div>
                        <div class="text-sm text-muted-foreground truncate">{{ submission.student.email }}</div>
                        <div class="text-xs text-muted-foreground mt-1">
                          {{ formatDate(submission.submitted_at) }}
                        </div>
                      </div>
                      <div class="flex flex-col items-end gap-1">
                        <Badge :variant="getStatusBadge(submission.status).variant">
                          {{ getStatusBadge(submission.status).label }}
                        </Badge>
                        <div v-if="submission.score !== null" class="text-sm font-semibold">
                          {{ submission.score }}/{{ assignment.points ?? 'N/A' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Grading Form -->
            <Card class="lg:col-span-2 h-[700px] flex flex-col" v-if="currentSubmission">
              <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                  <div>
                    <CardTitle>{{ currentSubmission.student.name }}</CardTitle>
                    <CardDescription>{{ currentSubmission.student.email }}</CardDescription>
                  </div>
                  <Badge :variant="getStatusBadge(currentSubmission.status).variant">
                    {{ getStatusBadge(currentSubmission.status).label }}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent class="space-y-4 flex-1 overflow-y-auto">
                <!-- Submission Content -->
                <div class="space-y-2">
                  <Label>Submission</Label>
                  <div class="border rounded-lg p-3 space-y-2">
                    <div v-if="currentSubmission.submission_type === 'link' && currentSubmission.link_url" class="flex items-center gap-2">
                      <LinkIcon class="h-4 w-4" />
                      <a :href="currentSubmission.link_url" target="_blank" class="text-blue-600 hover:underline">
                        {{ currentSubmission.link_url }}
                      </a>
                    </div>
                    <div v-if="currentSubmission.submission_type === 'video_link' && currentSubmission.video_url" class="flex items-center gap-2">
                      <Video class="h-4 w-4" />
                      <a :href="currentSubmission.video_url" target="_blank" class="text-blue-600 hover:underline">
                        {{ currentSubmission.video_url }}
                      </a>
                    </div>
                    <div v-if="currentSubmission.attachments.length > 0" class="space-y-2">
                      <div class="text-sm font-medium">Attachments:</div>
                      <div v-for="att in currentSubmission.attachments" :key="att.id" class="flex items-center gap-2">
                        <FileText class="h-4 w-4" />
                        <span class="flex-1 truncate text-sm">{{ att.name }}</span>
                        <Button variant="ghost" size="sm" @click="setPreviewFile(att)" :class="previewFile?.id === att.id ? 'bg-primary/10' : ''">
                          <Eye class="h-3 w-3" />
                        </Button>
                        <Button variant="ghost" size="sm" @click="window.open(att.url, '_blank')">
                          <Download class="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Rubric Selection -->
                <div class="space-y-2">
                  <Label>Rubric (Optional)</Label>
                  <Select v-model="selectedRubric" @update:model-value="individualForm.criteria_id = selectedRubric">
                    <SelectTrigger>
                      <SelectValue placeholder="Select a rubric" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">No Rubric</SelectItem>
                      <SelectItem v-for="rubric in rubrics" :key="rubric.id" :value="rubric.id">
                        {{ rubric.title }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Rubric Scoring -->
                <div v-if="currentRubric" class="space-y-4 border rounded-lg p-4">
                  <div class="flex items-center justify-between">
                    <Label>Rubric Scoring</Label>
                    <Button variant="outline" size="sm" @click="applyRubricScore">
                      Apply Calculated Score
                    </Button>
                  </div>
                  <div v-for="item in currentRubric.items" :key="item.id" class="space-y-2">
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="font-medium">{{ item.label }}</div>
                        <div v-if="item.description" class="text-sm text-muted-foreground">{{ item.description }}</div>
                        <div class="text-xs text-muted-foreground">Max: {{ item.points }} pts</div>
                      </div>
                      <Input
                        type="number"
                        :min="0"
                        :max="item.points"
                        v-model.number="individualForm.rubric_scores[item.id]"
                        class="w-24"
                        placeholder="0"
                      />
                    </div>
                  </div>
                  <div class="text-sm text-muted-foreground">
                    Calculated Score: {{ calculateRubricScore() }}/{{ assignment.points ?? 'N/A' }}
                  </div>
                </div>

                <!-- Score -->
                <div class="space-y-2">
                  <Label for="score">Score</Label>
                  <Input
                    id="score"
                    type="number"
                    :min="0"
                    :max="assignment.points ?? 999999"
                    v-model.number="individualForm.score"
                  />
                  <div class="text-sm text-muted-foreground">
                    Out of {{ assignment.points ?? 'N/A' }} points
                  </div>
                </div>

                <!-- Feedback -->
                <div class="space-y-2">
                  <Label for="feedback">Feedback</Label>
                  <Textarea
                    id="feedback"
                    v-model="individualForm.feedback"
                    rows="3"
                    placeholder="Enter feedback for the student..."
                  />
                </div>

                <!-- Grade Override -->
                <div class="space-y-2">
                  <div class="flex items-center gap-2">
                    <Checkbox id="override" v-model:checked="individualForm.grade_override" />
                    <Label for="override">Grade Override</Label>
                  </div>
                  <Textarea
                    v-if="individualForm.grade_override"
                    v-model="individualForm.override_reason"
                    rows="2"
                    placeholder="Reason for override..."
                  />
                </div>

                <!-- Return to Student -->
                <div class="flex items-center gap-2">
                  <Checkbox id="return" v-model:checked="individualForm.return_to_student" />
                  <Label for="return">Return submission with feedback to student</Label>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                  <Button @click="submitGrade" :disabled="individualForm.processing">
                    <Check class="h-4 w-4 mr-2" />
                    Grade Submission
                  </Button>
                  <Button variant="outline" @click="selectedSubmission = null">
                    Cancel
                  </Button>
                </div>
              </CardContent>
            </Card>

            <!-- File Preview Panel -->
            <Card class="lg:col-span-2 h-[700px]" v-if="currentSubmission">
              <CardHeader class="pb-3">
                <CardTitle>File Preview</CardTitle>
                <CardDescription>{{ currentSubmission.attachments.length }} attachment(s)</CardDescription>
              </CardHeader>
              <CardContent class="h-[calc(100%-80px)]">
                <FilePreviewEmbed :file="previewFile" />
              </CardContent>
            </Card>

            <!-- Empty State -->
            <Card class="lg:col-span-4" v-else>
              <CardContent class="flex items-center justify-center h-64">
                <div class="text-center text-muted-foreground">
                  <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
                  <p>Select a submission to grade</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Bulk Grading Tab -->
        <TabsContent value="bulk" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Bulk Grading</CardTitle>
              <CardDescription>Grade multiple submissions at once</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Checkbox id="return-bulk" v-model:checked="returnToStudents" />
                  <Label for="return-bulk">Return all graded submissions to students</Label>
                </div>
                <Button @click="submitBulkGrade" :disabled="bulkForm.processing || selectedForBulk.size === 0">
                  Grade Selected ({{ selectedForBulk.size }})
                </Button>
              </div>

              <div class="border rounded-lg overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="w-12">
                        <Checkbox
                          :checked="selectedForBulk.size === pendingSubmissions.length && pendingSubmissions.length > 0"
                          @update:checked="(checked) => {
                            if (checked) {
                              pendingSubmissions.forEach(s => selectedForBulk.add(s.id));
                            } else {
                              selectedForBulk.clear();
                            }
                            updateBulkForm();
                          }"
                        />
                      </TableHead>
                      <TableHead>Student</TableHead>
                      <TableHead>Submission Type</TableHead>
                      <TableHead>Submitted</TableHead>
                      <TableHead>Score</TableHead>
                      <TableHead>Feedback</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="submission in pendingSubmissions" :key="submission.id">
                      <TableCell>
                        <Checkbox
                          :checked="selectedForBulk.has(submission.id)"
                          @update:checked="toggleBulkSelection(submission.id)"
                        />
                      </TableCell>
                      <TableCell>
                        <div>
                          <div class="font-medium">{{ submission.student.name }}</div>
                          <div class="text-sm text-muted-foreground">{{ submission.student.email }}</div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{{ submission.submission_type }}</Badge>
                      </TableCell>
                      <TableCell class="text-sm">{{ formatDate(submission.submitted_at) }}</TableCell>
                      <TableCell>
                        <Input
                          type="number"
                          :min="0"
                          :max="assignment.points ?? 999999"
                          :model-value="getBulkScore(submission.id)"
                          @update:model-value="(value) => setBulkScore(submission.id, Number(value))"
                          class="w-24"
                        />
                      </TableCell>
                      <TableCell>
                        <Textarea
                          :model-value="getBulkFeedback(submission.id)"
                          @update:model-value="(value) => setBulkFeedback(submission.id, value)"
                          rows="2"
                          class="w-full"
                          placeholder="Feedback..."
                        />
                      </TableCell>
                    </TableRow>
                    <TableRow v-if="pendingSubmissions.length === 0">
                      <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                        No pending submissions to grade
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
