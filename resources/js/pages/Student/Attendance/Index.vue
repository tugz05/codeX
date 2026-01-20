<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutStudent.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Calendar, CheckCircle2, XCircle, AlertCircle, UserCheck, Clock, TrendingUp } from 'lucide-vue-next';

const props = defineProps<{
  classlist: {
    id: string;
    name: string;
    academic_year: string;
    room: string;
  };
  attendanceData: Array<{
    session_id: number;
    session_date: string;
    session_time: string | null;
    status: string;
    notes: string | null;
  }>;
  stats: {
    total_sessions: number;
    present: number;
    absent: number;
    late: number;
    excused: number;
    attendance_percentage: number;
  };
}>();

function getStatusIcon(status: string) {
  switch (status) {
    case 'present':
      return CheckCircle2;
    case 'absent':
      return XCircle;
    case 'late':
      return AlertCircle;
    case 'excused':
      return UserCheck;
    default:
      return Clock;
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'present':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'absent':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    case 'late':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'excused':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
}

function getStatusLabel(status: string) {
  switch (status) {
    case 'present':
      return 'Present';
    case 'absent':
      return 'Absent';
    case 'late':
      return 'Late';
    case 'excused':
      return 'Excused';
    default:
      return 'Unknown';
  }
}
</script>

<template>
  <Head :title="`Attendance - ${props.classlist.name}`" />
  
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="route('student.activities.index', props.classlist.id)" as="button">
          <Button variant="outline" size="sm">
            <ArrowLeft class="mr-2 h-4 w-4" /> Back
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold tracking-tight">My Attendance</h1>
          <p class="text-muted-foreground mt-1.5">{{ props.classlist.name }}</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Sessions</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.total_sessions }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present</CardTitle>
            <CheckCircle2 class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ props.stats.present }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Absent</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ props.stats.absent }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Attendance Rate</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.attendance_percentage }}%</div>
            <p class="text-xs text-muted-foreground mt-1">
              <Badge :variant="props.stats.attendance_percentage >= 80 ? 'default' : 'destructive'" class="text-xs">
                {{ props.stats.attendance_percentage >= 80 ? 'Good' : 'Needs Improvement' }}
              </Badge>
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Attendance History -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance History</CardTitle>
          <CardDescription>Your attendance records for this class</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.attendanceData.length > 0" class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Time</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Notes</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="record in props.attendanceData" :key="record.session_id">
                  <TableCell class="font-medium">
                    {{ new Date(record.session_date).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    }) }}
                  </TableCell>
                  <TableCell>
                    {{ record.session_time || 'N/A' }}
                  </TableCell>
                  <TableCell>
                    <Badge :class="getStatusColor(record.status)" class="flex items-center gap-1.5 w-fit">
                      <component :is="getStatusIcon(record.status)" class="h-3 w-3" />
                      {{ getStatusLabel(record.status) }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ record.notes || '-' }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <div v-else class="text-center py-12 text-muted-foreground">
            <Calendar class="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>No attendance records yet.</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
