<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { X, Download, FileText, Image, Video, File } from 'lucide-vue-next';

interface Props {
  file: {
    id: number;
    name: string;
    url: string;
    type: string | null;
    size: number | null;
  };
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

const fileType = computed(() => {
  if (!props.file.type) return 'unknown';
  if (props.file.type.startsWith('image/')) return 'image';
  if (props.file.type === 'application/pdf') return 'pdf';
  if (props.file.type.startsWith('video/')) return 'video';
  return 'other';
});

const fileIcon = computed(() => {
  switch (fileType.value) {
    case 'image':
      return Image;
    case 'pdf':
      return FileText;
    case 'video':
      return Video;
    default:
      return File;
  }
});

const formatFileSize = (bytes: number | null): string => {
  if (!bytes) return 'Unknown size';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const handleDownload = () => {
  window.open(props.file.url, '_blank');
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-4xl max-h-[90vh] p-0">
      <div class="relative flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b">
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <component :is="fileIcon" class="h-5 w-5 text-muted-foreground shrink-0" />
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold truncate">{{ file.name }}</h3>
              <p class="text-sm text-muted-foreground">{{ formatFileSize(file.size) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Button variant="outline" size="sm" @click="handleDownload">
              <Download class="h-4 w-4 mr-2" />
              Download
            </Button>
            <Button variant="ghost" size="icon" @click="isOpen = false">
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <!-- Preview Content -->
        <div class="flex-1 overflow-auto p-4 bg-muted/30">
          <!-- Image Preview -->
          <div v-if="fileType === 'image'" class="flex items-center justify-center h-full">
            <img
              :src="file.url"
              :alt="file.name"
              class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-lg"
            />
          </div>

          <!-- PDF Preview -->
          <div v-else-if="fileType === 'pdf'" class="flex items-center justify-center h-full">
            <iframe
              :src="file.url"
              class="w-full h-[70vh] border rounded-lg"
              frameborder="0"
            ></iframe>
          </div>

          <!-- Video Preview -->
          <div v-else-if="fileType === 'video'" class="flex items-center justify-center h-full">
            <video
              :src="file.url"
              controls
              class="max-w-full max-h-[70vh] rounded-lg shadow-lg"
            >
              Your browser does not support the video tag.
            </video>
          </div>

          <!-- Other Files -->
          <div v-else class="flex flex-col items-center justify-center h-full text-center p-8">
            <component :is="fileIcon" class="h-16 w-16 text-muted-foreground mb-4" />
            <p class="text-lg font-medium mb-2">{{ file.name }}</p>
            <p class="text-sm text-muted-foreground mb-4">{{ formatFileSize(file.size) }}</p>
            <Button @click="handleDownload">
              <Download class="h-4 w-4 mr-2" />
              Download File
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
