<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Users, Mail, Calendar } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  classlist: {
    id: string
    name: string
    section: string | null
    room: string
    academic_year: string
  }
  students: Array<{
    id: number
    name: string
    email: string
    joined_at: string | null
    status: string
  }>
  total_students: number
}>()

function formatDate(dateString: string | null): string {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head :title="`Students - ${props.classlist.name}`" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="route('instructor.classlist')" as="button">
          <Button variant="outline" size="sm">
            <ArrowLeft class="mr-2 h-4 w-4" /> Back
          </Button>
        </Link>
        <div class="flex-1">
          <h1 class="text-2xl font-bold tracking-tight">Students</h1>
          <p class="text-sm text-muted-foreground mt-1">{{ props.classlist.name }}</p>
        </div>
      </div>

      <!-- Class Info Card -->
      <Card class="border-2">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Users class="h-5 w-5" />
            Class Information
          </CardTitle>
          <CardDescription>
            Details about this class
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs font-medium text-muted-foreground mb-1">Section</p>
              <p class="text-sm font-semibold">{{ props.classlist.section || 'No section' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-muted-foreground mb-1">Room</p>
              <p class="text-sm font-semibold">{{ props.classlist.room }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-muted-foreground mb-1">Academic Year</p>
              <p class="text-sm font-semibold">{{ props.classlist.academic_year }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Students List Card -->
      <Card class="border-2">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="flex items-center gap-2">
                <Users class="h-5 w-5" />
                Enrolled Students
              </CardTitle>
              <CardDescription>
                List of all students who have joined this class
              </CardDescription>
            </div>
            <Badge variant="outline" class="text-sm px-3 py-1">
              {{ props.total_students }} {{ props.total_students === 1 ? 'Student' : 'Students' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="props.students.length === 0" class="text-center py-12">
            <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
            <h3 class="text-lg font-semibold mb-2">No Students Yet</h3>
            <p class="text-sm text-muted-foreground">No students have joined this class yet.</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(student, index) in props.students"
              :key="student.id"
              class="flex items-center justify-between p-4 border-2 rounded-lg"
            >
              <div class="flex items-center gap-4 flex-1">
                <div class="flex items-center justify-center w-10 h-10 border-2 rounded-full font-semibold text-sm">
                  {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-base font-semibold">{{ student.name }}</p>
                    <Badge v-if="student.status === 'active'" variant="outline" class="text-xs">
                      Active
                    </Badge>
                  </div>
                  <div class="flex items-center gap-4 text-sm text-muted-foreground">
                    <div class="flex items-center gap-1">
                      <Mail class="h-3 w-3" />
                      <span>{{ student.email }}</span>
                    </div>
                    <div v-if="student.joined_at" class="flex items-center gap-1">
                      <Calendar class="h-3 w-3" />
                      <span>Joined {{ formatDate(student.joined_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
