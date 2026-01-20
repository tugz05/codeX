<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Calendar, CheckCircle2, XCircle, AlertCircle, UserCheck, Download, FileText } from 'lucide-vue-next';

const props = defineProps<{
  classlist: {
    id: string;
    name: string;
    academic_year: string;
    room: string;
  };
  sessions: Array<{
    id: number;
    session_date: string;
    session_time: string | null;
  }>;
  reportData: Array<{
    student: {
      id: number;
      name: string;
      email: string;
    };
    records: Array<{
      session_id: number;
      session_date: string;
      status: string;
    }>;
    stats: {
      total_sessions: number;
      present: number;
      absent: number;
      late: number;
      excused: number;
      attendance_percentage: number;
    };
  }>;
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
      return Calendar;
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
      return 'P';
    case 'absent':
      return 'A';
    case 'late':
      return 'L';
    case 'excused':
      return 'E';
    default:
      return '-';
  }
}
</script>

<template>
  <Head :title="`Attendance Report - ${props.classlist.name}`" />
  
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="route('instructor.attendance.index', props.classlist.id)" as="button">
          <Button variant="outline" size="sm">
            <ArrowLeft class="mr-2 h-4 w-4" /> Back
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold tracking-tight">Attendance Report</h1>
          <p class="text-muted-foreground mt-1.5">{{ props.classlist.name }}</p>
        </div>
        <Button variant="outline" @click="window.print()">
          <Download class="mr-2 h-4 w-4" /> Print/Export
        </Button>
      </div>

      <!-- Summary Statistics -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Sessions</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.sessions.length }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <UserCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.reportData.length }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Attendance</CardTitle>
            <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ props.reportData.length > 0 
                ? Math.round(props.reportData.reduce((sum, data) => sum + data.stats.attendance_percentage, 0) / props.reportData.length) 
                : 0 }}%
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Students Above 80%</CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ props.reportData.filter(data => data.stats.attendance_percentage >= 80).length }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Detailed Report Table -->
      <Card>
        <CardHeader>
          <CardTitle>Detailed Attendance Report</CardTitle>
          <CardDescription>Complete attendance history for all students</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="sticky left-0 bg-background z-10">Student</TableHead>
                  <TableHead
                    v-for="session in props.sessions"
                    :key="session.id"
                    class="text-center min-w-[60px]"
                  >
                    <div class="flex flex-col items-center gap-1">
                      <span class="text-xs">{{ new Date(session.session_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
                      <span v-if="session.session_time" class="text-xs text-muted-foreground">{{ session.session_time }}</span>
                    </div>
                  </TableHead>
                  <TableHead class="text-center">Present</TableHead>
                  <TableHead class="text-center">Absent</TableHead>
                  <TableHead class="text-center">Late</TableHead>
                  <TableHead class="text-center">Excused</TableHead>
                  <TableHead class="text-center">Total</TableHead>
                  <TableHead class="text-center">Attendance %</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="data in props.reportData" :key="data.student.id">
                  <TableCell class="sticky left-0 bg-background z-10 font-medium">
                    <div>
                      <div>{{ data.student.name }}</div>
                      <div class="text-xs text-muted-foreground">{{ data.student.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell
                    v-for="session in props.sessions"
                    :key="session.id"
                    class="text-center"
                  >
                    <Badge
                      :class="getStatusColor(
                        data.records.find(r => r.session_id === session.id)?.status || 'absent'
                      )"
                      class="text-xs"
                    >
                      {{ getStatusLabel(data.records.find(r => r.session_id === session.id)?.status || 'absent') }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge :class="getStatusColor('present')">{{ data.stats.present }}</Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge :class="getStatusColor('absent')">{{ data.stats.absent }}</Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge :class="getStatusColor('late')">{{ data.stats.late }}</Badge>
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge :class="getStatusColor('excused')">{{ data.stats.excused }}</Badge>
                  </TableCell>
                  <TableCell class="text-center font-medium">{{ data.stats.total_sessions }}</TableCell>
                  <TableCell class="text-center">
                    <Badge :variant="data.stats.attendance_percentage >= 80 ? 'default' : 'destructive'">
                      {{ data.stats.attendance_percentage.toFixed(1) }}%
                    </Badge>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
