<script setup lang="ts">
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Clock, Download, FileText } from 'lucide-vue-next';
import FilePreview from './FilePreview.vue';

interface FileVersion {
  id: number;
  version: number;
  name: string;
  url: string;
  type: string | null;
  size: number | null;
  version_notes: string | null;
  is_current: boolean;
  created_at: string;
}

interface Props {
  versions: FileVersion[];
  currentVersion: FileVersion;
  open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const previewFile = ref<FileVersion | null>(null);
const showPreview = ref(false);

const sortedVersions = computed(() => {
  return [...props.versions].sort((a, b) => b.version - a.version);
});

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const formatFileSize = (bytes: number | null): string => {
  if (!bytes) return 'Unknown size';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const openPreview = (file: FileVersion) => {
  previewFile.value = file;
  showPreview.value = true;
};

const handleDownload = (url: string) => {
  window.open(url, '_blank');
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl max-h-[80vh]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <FileText class="h-5 w-5" />
          Version History
        </DialogTitle>
      </DialogHeader>
      <div class="overflow-y-auto max-h-[60vh]">
      <div class="space-y-3">
        <div
          v-for="version in sortedVersions"
          :key="version.id"
          class="flex items-start gap-4 p-4 border rounded-lg hover:bg-accent/50 transition-colors"
          :class="{ 'border-primary bg-primary/5': version.is_current }"
        >
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2">
              <Badge :variant="version.is_current ? 'default' : 'outline'">
                v{{ version.version }}
                <span v-if="version.is_current" class="ml-1">(Current)</span>
              </Badge>
              <span class="text-sm font-medium truncate">{{ version.name }}</span>
            </div>
            <div class="flex items-center gap-4 text-xs text-muted-foreground mb-2">
              <div class="flex items-center gap-1">
                <Clock class="h-3 w-3" />
                {{ formatDate(version.created_at) }}
              </div>
              <span>{{ formatFileSize(version.size) }}</span>
            </div>
            <p v-if="version.version_notes" class="text-sm text-muted-foreground mt-2">
              {{ version.version_notes }}
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <Button variant="outline" size="sm" @click="openPreview(version)">
              Preview
            </Button>
            <Button variant="ghost" size="icon" @click="handleDownload(version.url)">
              <Download class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      <FilePreview
        v-if="previewFile"
        :file="previewFile"
        v-model:open="showPreview"
      />
    </DialogContent>
  </Dialog>
</template>
