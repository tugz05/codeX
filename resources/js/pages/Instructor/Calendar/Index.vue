<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AuthLayoutInstructor.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Calendar, Download, Filter } from 'lucide-vue-next';
import type { EventInput } from '@fullcalendar/core';

interface Props {
  events: Array<{
    id: string;
    title: string;
    type: string;
    classlist_id: string;
    classlist_name: string;
    start: string;
    end: string;
    points: number | null;
    color: string;
  }>;
  classlists: Array<{
    id: string;
    name: string;
    academic_year: string;
  }>;
}

const props = defineProps<Props>();

const selectedClasslists = ref<Set<string>>(new Set(props.classlists.map(cl => cl.id)));
const selectedTypes = ref<Set<string>>(new Set(['activity', 'assignment', 'quiz', 'examination']));
const calendarRef = ref();

const filteredEvents = computed<EventInput[]>(() => {
  return props.events
    .filter(event => {
      // Show all if no filters selected, or show only selected
      if (selectedClasslists.value.size > 0 && !selectedClasslists.value.has(event.classlist_id)) {
        return false;
      }
      if (selectedTypes.value.size > 0 && !selectedTypes.value.has(event.type)) {
        return false;
      }
      return true;
    })
    .map(event => {
      // Create a more concise title
      const title = event.title.length > 30 ? event.title.substring(0, 30) + '...' : event.title;
      const classAbbr = event.classlist_name.length > 15 
        ? event.classlist_name.substring(0, 15) + '...' 
        : event.classlist_name;
      
      return {
        id: event.id,
        title: `${title} (${classAbbr})`,
        start: event.start,
        end: event.end,
        backgroundColor: event.color,
        borderColor: event.color,
        textColor: '#ffffff',
        classNames: ['calendar-event', `event-type-${event.type}`],
        extendedProps: {
          type: event.type,
          classlist_name: event.classlist_name,
          points: event.points,
          fullTitle: event.title,
        },
      };
    });
});

const toggleClasslist = (classlistId: string) => {
  if (selectedClasslists.value.has(classlistId)) {
    selectedClasslists.value.delete(classlistId);
  } else {
    selectedClasslists.value.add(classlistId);
  }
};

const toggleType = (type: string) => {
  if (selectedTypes.value.has(type)) {
    selectedTypes.value.delete(type);
  } else {
    selectedTypes.value.add(type);
  }
};

const selectAllClasslists = () => {
  if (selectedClasslists.value.size === props.classlists.length) {
    selectedClasslists.value.clear();
  } else {
    props.classlists.forEach(cl => selectedClasslists.value.add(cl.id));
  }
};

const selectAllTypes = () => {
  const allTypes = ['activity', 'assignment', 'quiz', 'examination'];
  if (selectedTypes.value.size === allTypes.length) {
    selectedTypes.value.clear();
  } else {
    allTypes.forEach(type => selectedTypes.value.add(type));
  }
};

const exportCalendar = () => {
  const classlistIds = Array.from(selectedClasslists.value);
  const types = Array.from(selectedTypes.value);
  
  const params = new URLSearchParams();
  if (classlistIds.length > 0) {
    classlistIds.forEach(id => params.append('classlist_ids[]', id));
  }
  if (types.length > 0) {
    types.forEach(type => params.append('types[]', type));
  }
  
  window.location.href = route('instructor.calendar.export') + '?' + params.toString();
};

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    activity: 'Activity',
    assignment: 'Assignment',
    quiz: 'Quiz',
    examination: 'Examination',
  };
  return labels[type] || type;
};

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    activity: '#22c55e',
    assignment: '#6366f1',
    quiz: '#3b82f6',
    examination: '#a855f7',
  };
  return colors[type] || '#gray';
};

const eventStats = computed(() => {
  const filtered = filteredEvents.value;
  return {
    total: filtered.length,
    byType: {
      activity: filtered.filter(e => e.extendedProps?.type === 'activity').length,
      assignment: filtered.filter(e => e.extendedProps?.type === 'assignment').length,
      quiz: filtered.filter(e => e.extendedProps?.type === 'quiz').length,
      examination: filtered.filter(e => e.extendedProps?.type === 'examination').length,
    },
  };
});

const handleEventClick = (info: any) => {
  const event = info.event;
  const [type, id] = event.id.split('-');
  const classlistId = props.events.find(e => e.id === event.id)?.classlist_id;
  
  if (!classlistId) return;
  
  const routes: Record<string, (classlistId: string, id: string) => string> = {
    activity: (cl, id) => route('instructor.activities.index', cl),
    assignment: (cl, id) => route('instructor.assignments.show', [cl, id]),
    quiz: (cl, id) => route('instructor.quizzes.show', [cl, id]),
    examination: (cl, id) => route('instructor.examinations.show', [cl, id]),
  };
  
  const routeFn = routes[type];
  if (routeFn) {
    router.visit(routeFn(classlistId, id));
  }
};

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  events: filteredEvents.value,
  eventClick: handleEventClick,
  height: 'auto',
  eventDisplay: 'block',
  editable: false,
  selectable: false,
  eventMaxStack: 3,
  moreLinkClick: 'popover',
  dayMaxEvents: 3,
  eventTimeFormat: {
    hour: 'numeric',
    minute: '2-digit',
    meridiem: 'short',
  },
  eventClassNames: 'calendar-event',
  eventDidMount: (arg: any) => {
    // Add tooltip with full title
    const fullTitle = arg.event.extendedProps.fullTitle || arg.event.title;
    const classlistName = arg.event.extendedProps.classlist_name || '';
    const points = arg.event.extendedProps.points;
    const type = arg.event.extendedProps.type;
    
    arg.el.setAttribute('title', `${fullTitle} - ${classlistName}${points ? ` (${points} pts)` : ''}`);
    arg.el.classList.add(`event-type-${type}`);
  },
}));
</script>

<template>
  <Head title="Calendar" />
  <AppLayout>
    <div class="container mx-auto p-4 sm:p-6 max-w-[1600px]">
      <!-- Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Calendar</h1>
            <p class="text-sm sm:text-base text-muted-foreground mt-1">View all deadlines and important dates across your classes</p>
          </div>
          <Button @click="exportCalendar" variant="outline" size="sm" class="w-full sm:w-auto shrink-0">
            <Download class="h-4 w-4 mr-2" />
            Export iCal
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        <!-- Filters Sidebar -->
        <Card class="lg:col-span-3 xl:col-span-2 h-fit lg:sticky lg:top-6">
          <CardHeader class="pb-3 px-4 sm:px-6">
            <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
              <Filter class="h-4 w-4" />
              Filters
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-5 sm:space-y-6 px-4 sm:px-6 pb-4 sm:pb-6">
            <!-- Class Filter -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <Label class="text-sm font-semibold">Classes</Label>
                <Button variant="ghost" size="sm" @click="selectAllClasslists" class="text-xs h-7 px-2">
                  {{ selectedClasslists.size === classlists.length ? 'Deselect All' : 'Select All' }}
                </Button>
              </div>
              <div class="space-y-2 max-h-48 sm:max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                <div 
                  v-for="classlist in classlists" 
                  :key="classlist.id" 
                  class="flex items-center gap-2 p-2 rounded-md hover:bg-accent/50 transition-colors cursor-pointer"
                >
                  <Checkbox
                    :id="`class-${classlist.id}`"
                    :checked="selectedClasslists.has(classlist.id)"
                    @update:checked="toggleClasslist(classlist.id)"
                  />
                  <Label :for="`class-${classlist.id}`" class="text-sm cursor-pointer flex-1 truncate">
                    {{ classlist.name }}
                  </Label>
                </div>
              </div>
            </div>

            <!-- Type Filter -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <Label class="text-sm font-semibold">Content Types</Label>
                <Button variant="ghost" size="sm" @click="selectAllTypes" class="text-xs h-7 px-2">
                  {{ selectedTypes.size === 4 ? 'Deselect All' : 'Select All' }}
                </Button>
              </div>
              <div class="space-y-2">
                <div 
                  v-for="type in ['activity', 'assignment', 'quiz', 'examination']" 
                  :key="type" 
                  class="flex items-center gap-2 p-2 rounded-md hover:bg-accent/50 transition-colors cursor-pointer"
                >
                  <Checkbox
                    :id="`type-${type}`"
                    :checked="selectedTypes.has(type)"
                    @update:checked="toggleType(type)"
                  />
                  <Label :for="`type-${type}`" class="text-sm cursor-pointer flex items-center gap-2 flex-1">
                    <span
                      class="w-3 h-3 rounded-full border border-border/50 shrink-0"
                      :style="{ backgroundColor: getTypeColor(type) }"
                    ></span>
                    <span class="truncate">{{ getTypeLabel(type) }}</span>
                  </Label>
                </div>
              </div>
            </div>

            <!-- Statistics -->
            <div class="space-y-2 pt-4 border-t">
              <Label class="text-sm font-semibold">Statistics</Label>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                  <span class="text-muted-foreground">Total Events</span>
                  <Badge variant="secondary" class="font-semibold">{{ eventStats?.total ?? 0 }}</Badge>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-muted-foreground">Activities</span>
                  <Badge variant="outline" class="font-semibold">{{ eventStats?.byType?.activity ?? 0 }}</Badge>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-muted-foreground">Assignments</span>
                  <Badge variant="outline" class="font-semibold">{{ eventStats?.byType?.assignment ?? 0 }}</Badge>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-muted-foreground">Quizzes</span>
                  <Badge variant="outline" class="font-semibold">{{ eventStats?.byType?.quiz ?? 0 }}</Badge>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-muted-foreground">Examinations</span>
                  <Badge variant="outline" class="font-semibold">{{ eventStats?.byType?.examination ?? 0 }}</Badge>
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div class="space-y-2 pt-4 border-t">
              <Label class="text-sm font-semibold">Legend</Label>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-green-500 border border-border/50"></span>
                  <span>Activity</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-indigo-500 border border-border/50"></span>
                  <span>Assignment</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-blue-500 border border-border/50"></span>
                  <span>Quiz</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-purple-500 border border-border/50"></span>
                  <span>Examination</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Calendar -->
        <Card class="lg:col-span-9 xl:col-span-10">
          <CardContent class="p-3 sm:p-4 md:p-6">
            <div class="calendar-wrapper">
              <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.fc {
  font-family: inherit;
}

.fc-theme-standard td,
.fc-theme-standard th {
  border-color: hsl(var(--border));
}

.fc-theme-standard .fc-scrollgrid {
  border-color: hsl(var(--border));
}

.fc-button-primary {
  background-color: hsl(var(--primary));
  border-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
}

.fc-button-primary:hover:not(:disabled) {
  background-color: hsl(var(--primary) / 0.9);
  border-color: hsl(var(--primary) / 0.9);
}

.fc-button-primary:disabled {
  opacity: 0.5;
}

.fc-button-active {
  background-color: hsl(var(--primary));
  border-color: hsl(var(--primary));
}

.fc-daygrid-day-number {
  color: hsl(var(--foreground));
  font-weight: 500;
}

.fc-col-header-cell {
  background-color: hsl(var(--muted) / 0.5);
  padding: 0.75rem 0;
}

.fc-col-header-cell-cushion {
  color: hsl(var(--foreground));
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.fc-day-today {
  background-color: hsl(var(--accent) / 0.3) !important;
}

.fc-event {
  cursor: pointer;
  border-radius: 0.375rem;
  padding: 0.25rem 0.5rem;
  margin: 0.125rem 0;
  font-size: 0.75rem;
  font-weight: 500;
  border: none;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  transition: all 0.2s;
}

.fc-event:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px 0 rgb(0 0 0 / 0.1);
}

.fc-event-main-frame {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
}

.fc-event-time {
  font-weight: 600;
  font-size: 0.7rem;
  opacity: 0.9;
  flex-shrink: 0;
}

.fc-event-title {
  font-weight: 500;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.fc-daygrid-event {
  white-space: normal;
  word-wrap: break-word;
}

.fc-more-link {
  color: hsl(var(--primary));
  font-weight: 500;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  background-color: hsl(var(--accent));
}

.fc-more-link:hover {
  background-color: hsl(var(--accent) / 0.8);
}

.fc-popover {
  background-color: hsl(var(--popover));
  border-color: hsl(var(--border));
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.fc-popover-header {
  background-color: hsl(var(--muted));
  border-color: hsl(var(--border));
  padding: 0.75rem;
}

.fc-popover-title {
  color: hsl(var(--foreground));
  font-weight: 600;
}

.fc-popover-close {
  color: hsl(var(--muted-foreground));
}

.fc-popover-close:hover {
  color: hsl(var(--foreground));
}

.fc-toolbar-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: hsl(var(--foreground));
}

.fc-toolbar-chunk {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.fc-button {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.fc-button:focus {
  outline: 2px solid hsl(var(--ring));
  outline-offset: 2px;
}

.fc-daygrid-day-frame {
  min-height: 120px;
}

.fc-daygrid-day {
  position: relative;
}

.fc-daygrid-day-events {
  position: relative;
  z-index: 1;
}

.fc-daygrid-event {
  margin-bottom: 0.125rem;
}

.fc-daygrid-event:last-child {
  margin-bottom: 0;
}

.fc-daygrid-day-events {
  margin-top: 0.25rem;
}

.fc-daygrid-day-top {
  padding: 0.5rem;
}

.fc-daygrid-day-number {
  padding: 0.25rem;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .fc-header-toolbar {
    flex-direction: column;
    gap: 0.5rem;
  }

  .fc-toolbar-chunk {
    width: 100%;
    justify-content: space-between;
  }

  .fc-button-group {
    display: flex;
    width: 100%;
  }

  .fc-button {
    flex: 1;
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
  }

  .fc-toolbar-title {
    font-size: 1.25rem;
    text-align: center;
  }

  .fc-daygrid-day-frame {
    min-height: 80px;
  }

  .fc-event {
    font-size: 0.65rem;
    padding: 0.125rem 0.375rem;
  }

  .fc-daygrid-day-number {
    font-size: 0.875rem;
  }

  .fc-col-header-cell-cushion {
    font-size: 0.65rem;
    padding: 0.5rem 0.25rem;
  }
}

.calendar-wrapper {
  width: 100%;
  overflow-x: auto;
}

@media (max-width: 640px) {
  .fc-daygrid-day-frame {
    min-height: 60px;
  }
}

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: hsl(var(--muted));
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.3);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground) / 0.5);
}
</style>
