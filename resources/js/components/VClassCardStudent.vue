<script setup lang="ts">
import { computed } from 'vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EllipsisVertical, MapPin, GraduationCap, Calendar, Archive, LogOut, BookOpen, Clock, ArrowRight } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

type CardAction = 'archive' | 'unenroll' | 'edit' | 'delete' | 'copy-link' | 'restore';

const props = defineProps<{
  id: string;
  title: string;
  section: string;
  room: string;
  academicYear?: string;
  joinedAt?: string;
  toUrl?: string;
  options?: Array<{
    label: string;
    color?: string;
    action: CardAction;
  }>;
}>();

const emit = defineEmits<{ (event: CardAction, id: string): void }>();

function handleAction(action: CardAction, id: string) {
  emit(action, id);
}

function formatDate(dateString?: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
  },
  purple: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  green: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  orange: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  pink: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  indigo: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  teal: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
  },
  amber: {
    gradient: 'from-foreground/10 via-foreground/5 to-transparent',
    border: 'border-border',
    badge: 'bg-muted text-foreground',
    icon: 'text-foreground',
    hover: 'hover:border-foreground/40',
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
      :is="toUrl ? Link : 'div'"
      :href="toUrl ?? route('student.activities.index', id)"
      class="block"
    >
      <!-- Header / main content (compact, Google‑classroom style) -->
      <div
        class="relative px-5 py-4 bg-gradient-to-br border-b transition-all duration-300 group-hover:shadow-inner"
        :class="[currentColor.gradient, currentColor.border]"
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
              <div
                v-if="section && section !== 'No section'"
                class="inline-flex items-center gap-1.5"
              >
                <GraduationCap :class="['h-3.5 w-3.5', currentColor.icon]" />
                <span class="truncate max-w-[140px]">{{ section }}</span>
              </div>
              <span class="text-muted-foreground/50">•</span>
              <div class="inline-flex items-center gap-1.5">
                <MapPin :class="['h-3.5 w-3.5', currentColor.icon]" />
                <span>Room {{ room }}</span>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1 text-[11px] text-muted-foreground">
              <div v-if="joinedAt" class="inline-flex items-center gap-1.5">
                <Clock class="h-3.5 w-3.5" />
                <span>Joined {{ formatDate(joinedAt) }}</span>
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
      <div v-if="toUrl" class="px-5 py-2 bg-card">
        <Button
          variant="ghost"
          size="sm"
          class="w-full group/btn justify-between h-9"
          @click.stop
        >
          <span class="flex items-center gap-2 text-sm">
            <BookOpen class="h-4 w-4" />
            View class
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
        <span class="text-[11px] text-muted-foreground font-medium tracking-wide uppercase">Active</span>
      </div>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button class="text-muted-foreground transition-all duration-200 hover:text-foreground hover:bg-muted rounded-md p-1.5 hover:scale-110">
            <EllipsisVertical class="h-4 w-4" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-48" align="end">
          <DropdownMenuItem
            v-for="option in options"
            :key="option.action"
            @click="handleAction(option.action, id)"
          >
            <component
              :is="option.action === 'archive' ? Archive : option.action === 'restore' ? Archive : LogOut"
              class="mr-2 h-4 w-4"
            />
            <span :class="option.color">{{ option.label }}</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </div>
</template>
