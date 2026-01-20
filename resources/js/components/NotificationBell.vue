<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Check, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
// Simple date formatter
const formatDistanceToNow = (date: Date) => {
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
  
  if (diffInSeconds < 60) return 'just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
  return date.toLocaleDateString();
};

const page = usePage();
const unreadCount = ref(page.props.auth?.unread_notifications_count || 0);
const notifications = ref<any[]>([]);
const isLoading = ref(false);
const isOpen = ref(false);

const fetchNotifications = async () => {
  if (isLoading.value) return;
  // Don't fetch if user is not authenticated
  if (!page.props.auth?.user) return;
  
  isLoading.value = true;
  
  try {
    const accountType = page.props.auth?.user?.account_type || 'student';
    const routePrefix = accountType === 'instructor' ? 'instructor' : 'student';
    const response = await fetch(route(`${routePrefix}.notifications.index`, {}, { absolute: false }) + '?per_page=10', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });
    
    if (response.ok) {
      const data = await response.json();
      notifications.value = data.notifications || [];
      unreadCount.value = data.unread_count || 0;
    }
  } catch (error) {
    console.error('Failed to fetch notifications:', error);
  } finally {
    isLoading.value = false;
  }
};

const markAsRead = async (notificationId: string) => {
  try {
    const accountType = page.props.auth?.user?.account_type || 'student';
    const routePrefix = accountType === 'instructor' ? 'instructor' : 'student';
    await fetch(route(`${routePrefix}.notifications.read`, { notification: notificationId }, { absolute: false }), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
      notification.read_at = new Date().toISOString();
      if (unreadCount.value > 0) {
        unreadCount.value--;
      }
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
};

const markAllAsRead = async () => {
  try {
    const accountType = page.props.auth?.user?.account_type || 'student';
    const routePrefix = accountType === 'instructor' ? 'instructor' : 'student';
    await fetch(route(`${routePrefix}.notifications.read-all`, {}, { absolute: false }), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    
    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString();
    });
    unreadCount.value = 0;
  } catch (error) {
    console.error('Failed to mark all as read:', error);
  }
};

const deleteNotification = async (notificationId: string) => {
  try {
    const accountType = page.props.auth?.user?.account_type || 'student';
    const routePrefix = accountType === 'instructor' ? 'instructor' : 'student';
    await fetch(route(`${routePrefix}.notifications.destroy`, { notification: notificationId }, { absolute: false }), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    
    notifications.value = notifications.value.filter(n => n.id !== notificationId);
    if (notifications.value.find(n => n.id === notificationId && !n.read_at)) {
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  } catch (error) {
    console.error('Failed to delete notification:', error);
  }
};

const handleNotificationClick = (notification: any) => {
  if (!notification.read_at) {
    markAsRead(notification.id);
  }
  if (notification.action_url) {
    router.visit(notification.action_url);
  }
};

onMounted(() => {
  fetchNotifications();
  // Poll for new notifications every 30 seconds
  const interval = setInterval(fetchNotifications, 30000);
  onUnmounted(() => clearInterval(interval));
});

const getNotificationIcon = (typeKey: string) => {
  switch (typeKey) {
    case 'assignment_created':
      return '📝';
    case 'quiz_created':
      return '📋';
    case 'activity_created':
      return '💻';
    case 'material_created':
      return '📚';
    case 'examination_created':
      return '📝';
    case 'grade_released':
      return '✅';
    case 'due_date_reminder':
      return '⏰';
    case 'announcement':
      return '📢';
    default:
      return '🔔';
  }
};
</script>

<template>
  <DropdownMenu v-model:open="isOpen">
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="relative">
        <Bell class="h-5 w-5" />
        <Badge
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs text-white"
          variant="destructive"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </Badge>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-80">
      <div class="flex items-center justify-between p-2">
        <h3 class="font-semibold text-sm">Notifications</h3>
        <Button
          v-if="unreadCount > 0"
          variant="ghost"
          size="sm"
          class="h-7 text-xs"
          @click="markAllAsRead"
        >
          Mark all read
        </Button>
      </div>
      <DropdownMenuSeparator />
      <div class="max-h-96 overflow-y-auto">
        <div v-if="isLoading && notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
          Loading...
        </div>
        <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
          No notifications
        </div>
        <div v-else class="divide-y">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="p-3 hover:bg-accent cursor-pointer transition-colors relative group"
            :class="{ 'bg-accent/50': !notification.read_at }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex gap-3">
              <div class="text-2xl">{{ getNotificationIcon(notification.type_key) }}</div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm">{{ notification.title }}</p>
                <p class="text-xs text-muted-foreground mt-1 line-clamp-2">
                  {{ notification.message }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                  {{ formatDistanceToNow(new Date(notification.created_at)) }}
                </p>
              </div>
              <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                @click.stop="deleteNotification(notification.id)"
              >
                <X class="h-3 w-3" />
              </Button>
            </div>
            <div
              v-if="!notification.read_at"
              class="absolute left-0 top-0 bottom-0 w-1 bg-primary"
            />
          </div>
        </div>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
