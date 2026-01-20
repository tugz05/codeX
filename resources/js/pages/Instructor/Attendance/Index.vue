<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import AttendancePanel from '@/components/AttendancePanel.vue'

const props = defineProps<{
  classlist: {
    id: string
    name: string
    academic_year: string
    room: string
  }
  sessions: Array<{
    id: number
    session_date: string
    session_time: string | null
    notes: string | null
    total_students: number
    present_count: number
    absent_count: number
    late_count: number
    excused_count: number
  }>
  students: Array<{
    id: number
    name: string
    email: string
  }>
  studentStats: Array<{
    student: {
      id: number
      name: string
      email: string
    }
    total_sessions: number
    present: number
    absent: number
    late: number
    excused: number
    attendance_percentage: number
  }>
}>()
</script>

<template>
  <Head :title="`Attendance - ${props.classlist.name}`" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 overflow-x-auto p-3 sm:p-4 md:p-6 max-w-[1600px] mx-auto w-full">
      <AttendancePanel
        :classlist="props.classlist"
        :sessions="props.sessions"
        :students="props.students"
        :student-stats="props.studentStats"
        :show-back="true"
      />
    </div>
  </AppLayout>
</template>
