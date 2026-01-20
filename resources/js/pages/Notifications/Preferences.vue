<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue';
import AuthLayoutInstructor from '@/layouts/AuthLayoutInstructor.vue';
import { usePage } from '@inertiajs/vue3';
import { Bell, Mail, Smartphone } from 'lucide-vue-next';

const page = usePage();
const accountType = page.props.auth?.user?.account_type || 'student';
const Layout = accountType === 'instructor' ? AuthLayoutInstructor : AuthLayoutStudent;

interface Preferences {
  assignment_created_email: boolean;
  assignment_created_in_app: boolean;
  quiz_created_email: boolean;
  quiz_created_in_app: boolean;
  grade_released_email: boolean;
  grade_released_in_app: boolean;
  due_date_reminder_email: boolean;
  due_date_reminder_in_app: boolean;
  announcement_email: boolean;
  announcement_in_app: boolean;
}

interface Props {
  preferences: Preferences;
}

const props = defineProps<Props>();

const form = useForm({
  assignment_created_email: props.preferences.assignment_created_email,
  assignment_created_in_app: props.preferences.assignment_created_in_app,
  quiz_created_email: props.preferences.quiz_created_email,
  quiz_created_in_app: props.preferences.quiz_created_in_app,
  grade_released_email: props.preferences.grade_released_email,
  grade_released_in_app: props.preferences.grade_released_in_app,
  due_date_reminder_email: props.preferences.due_date_reminder_email,
  due_date_reminder_in_app: props.preferences.due_date_reminder_in_app,
  announcement_email: props.preferences.announcement_email,
  announcement_in_app: props.preferences.announcement_in_app,
});

const submit = () => {
  form.put(route(`${accountType}.notifications.preferences.update`), {
    preserveScroll: true,
    onSuccess: () => {
      // Show success message
    },
  });
};

const notificationTypes = [
  {
    key: 'assignment_created',
    title: 'New Assignments',
    description: 'Get notified when new assignments are posted',
  },
  {
    key: 'quiz_created',
    title: 'New Quizzes',
    description: 'Get notified when new quizzes are available',
  },
  {
    key: 'activity_created',
    title: 'New Activities',
    description: 'Get notified when new activities are posted',
  },
  {
    key: 'material_created',
    title: 'New Materials',
    description: 'Get notified when new materials are posted',
  },
  {
    key: 'examination_created',
    title: 'New Examinations',
    description: 'Get notified when new examinations are available',
  },
  {
    key: 'grade_released',
    title: 'Grade Releases',
    description: 'Get notified when your grades are released',
  },
  {
    key: 'due_date_reminder',
    title: 'Due Date Reminders',
    description: 'Get reminded before assignments and activities are due',
  },
  {
    key: 'announcement',
    title: 'Announcements',
    description: 'Get notified about important announcements',
  },
];
</script>

<template>
  <Head title="Notification Preferences" />
  <Layout>
    <div class="container mx-auto p-6 max-w-4xl">
      <div class="mb-6">
        <h1 class="text-2xl font-bold">Notification Preferences</h1>
        <p class="text-muted-foreground">Manage how you receive notifications</p>
      </div>

      <form @submit.prevent="submit">
        <div class="space-y-4">
          <Card
            v-for="type in notificationTypes"
            :key="type.key"
          >
            <CardHeader>
              <CardTitle>{{ type.title }}</CardTitle>
              <CardDescription>{{ type.description }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Mail class="h-4 w-4 text-muted-foreground" />
                  <Label :for="`${type.key}_email`">Email Notifications</Label>
                </div>
                <Switch
                  :id="`${type.key}_email`"
                  v-model="form[`${type.key}_email` as keyof typeof form]"
                />
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Smartphone class="h-4 w-4 text-muted-foreground" />
                  <Label :for="`${type.key}_in_app`">In-App Notifications</Label>
                </div>
                <Switch
                  :id="`${type.key}_in_app`"
                  v-model="form[`${type.key}_in_app` as keyof typeof form]"
                />
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            @click="router.visit(route(`${accountType}.dashboard`))"
          >
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            Save Preferences
          </Button>
        </div>
      </form>
    </div>
  </Layout>
</template>
