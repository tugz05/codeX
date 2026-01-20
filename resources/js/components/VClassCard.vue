<script setup lang="ts">
import { computed, ref } from 'vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EllipsisVertical, Users, MapPin, GraduationCap, Copy, Edit, Archive, Trash2, BookOpen, Calendar, ArrowRight, TrendingUp, Share2, UserCheck } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import ClassShareDialog from './ClassShareDialog.vue';

const props = defineProps<{
  id: string;
  title: string;
  section: string;
  room: string;
  studentsCount?: number;
  academicYear?: string;
  createUrl?: string;
  isArchived?: boolean;
}>();

const isArchived = computed(() => props.isArchived ?? false);

const emit = defineEmits<{
  edit: [];
  delete: [id: string];
  archive: [id: string];
  restore: [id: string];
}>();

const showShareDialog = ref(false);

function copyInviteLink() {
  const inviteLink = `${window.location.origin}/student/class-join?code=${props.id}`;
  navigator.clipboard.writeText(inviteLink).then(() => {
    toast.success('Invite link copied to clipboard!');
  });
}

function openShareDialog() {
  showShareDialog.value = true;
}

// Generate color scheme based on class name hash for categorization
const getClassColor = computed(() => {
  if (!props.title) return 'primary';
  const hash = props.title.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
  const colors = ['blue', 'purple', 'green', 'orange', 'pink', 'indigo', 'teal', 'amber'];
  return colors[hash % colors.length];
});

// Monochrome (black & white) motif for all classes
const colorClasses = {
  blue: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  purple: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  green: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  orange: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  pink: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  indigo: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  teal: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
  amber: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
    stat: 'bg-muted/40',
  },
};

const currentColor = computed(() => colorClasses[getClassColor.value as keyof typeof colorClasses] || colorClasses.blue);
</script>

<template>
  <div
    class="group relative w-full overflow-hidden rounded-2xl border bg-card shadow-md transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
    :class="[currentColor.border, currentColor.hover]"
  >
    <!-- Color accent bar -->
    <div
      class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r transition-all duration-300 group-hover:h-1.5"
      :class="currentColor.gradient.replace('/20', '/40').replace('/10', '/20')"
    ></div>

    <!-- Clickable area (header + content) -->
    <component
      :is="props.createUrl ? Link : 'div'"
      :href="props.createUrl"
      class="block"
    >
      <!-- Header / main content (compact, Google‑classroom style) -->
      <div
        class="relative px-5 py-4 bg-gradient-to-br border-b transition-all duration-300 group-hover:shadow-inner"
        :class="[currentColor.gradient, currentColor.border, props.createUrl && 'cursor-pointer']"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div
            class="flex h-12 w-12 items-center justify-center rounded-lg shadow-sm bg-muted"
          >
            <BookOpen :class="['h-6 w-6', currentColor.icon]" />
          </div>

          <!-- Text content -->
          <div class="flex-1 min-w-0 space-y-1">
            <h2 class="truncate text-lg font-semibold text-foreground group-hover:text-primary transition-colors">
              {{ title }}
            </h2>

            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              <div class="inline-flex items-center gap-1.5">
                <GraduationCap class="h-3.5 w-3.5" />
                <span class="truncate max-w-[140px]">{{ section }}</span>
              </div>
              <span class="text-muted-foreground/50">•</span>
              <div class="inline-flex items-center gap-1.5">
                <MapPin class="h-3.5 w-3.5" />
                <span>Room {{ room }}</span>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1 text-xs">
              <div class="flex items-center gap-1.5 text-muted-foreground">
                <Users class="h-3.5 w-3.5" />
                <span>{{ studentsCount || 0 }} students</span>
              </div>
              <div v-if="academicYear">
                <Badge
                  variant="outline"
                  class="text-[10px] font-medium px-2 py-0.5"
                  :class="currentColor.badge"
                >
                  <Calendar class="h-3 w-3 mr-1" />
                  AY {{ academicYear }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Access Button (subtle) -->
      <div v-if="props.createUrl" class="px-5 py-2 bg-card">
        <Button
          variant="ghost"
          size="sm"
          class="w-full group/btn justify-between h-9"
          @click.stop
        >
          <span class="flex items-center gap-2 text-sm">
            <TrendingUp class="h-4 w-4" />
            Manage class
          </span>
          <ArrowRight class="h-4 w-4 transition-transform group-hover/btn:translate-x-1" />
        </Button>
      </div>
    </component>

    <!-- Footer with dropdown (don't navigate when clicking menu) -->
    <div
      class="flex items-center justify-between border-t bg-muted/20 px-4 py-2.5 backdrop-blur-sm text-xs"
      :class="currentColor.border"
      @click.stop
    >
      <div class="flex items-center gap-2">
        <div
          class="h-2 w-2 rounded-full transition-all duration-300 group-hover:scale-125"
          :class="currentColor.badge.split(' ')[0]"
        ></div>
        <span class="text-[11px] text-muted-foreground font-medium tracking-wide uppercase">
          {{ isArchived ? 'Archived' : 'Active' }}
        </span>
      </div>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="icon"
          class="h-7 w-7 border-border text-muted-foreground hover:text-foreground"
          @click.stop="openShareDialog"
        >
          <Share2 class="h-3.5 w-3.5" />
        </Button>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button class="text-muted-foreground transition-all duration-200 hover:text-foreground hover:bg-muted rounded-md p-1.5 hover:scale-110">
              <EllipsisVertical class="h-4 w-4" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent class="w-48" align="end">
            <DropdownMenuItem :as-child="true">
              <Link :href="route('instructor.gradebook.index', id)" class="block w-full">
                <BookOpen class="mr-2 h-4 w-4" />
                Gradebook
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem @click="copyInviteLink">
              <Copy class="mr-2 h-4 w-4" />
              Copy invite link
            </DropdownMenuItem>
            <DropdownMenuItem @click="emit('edit')">
              <Edit class="mr-2 h-4 w-4" />
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem v-if="!isArchived" @click="emit('archive', id)">
              <Archive class="mr-2 h-4 w-4" />
              Archive
            </DropdownMenuItem>
            <DropdownMenuItem v-if="isArchived" @click="emit('restore', id)">
              <Archive class="mr-2 h-4 w-4" />
              Restore
            </DropdownMenuItem>
            <DropdownMenuItem v-if="!isArchived" @click="emit('delete', id)" class="text-destructive focus:text-destructive">
              <Trash2 class="mr-2 h-4 w-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Share Dialog -->
    <ClassShareDialog
      v-model:open="showShareDialog"
      :class-id="id"
      :class-name="title"
    />
  </div>
</template>
