<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { X, Download, FileText, Image, Video, File } from 'lucide-vue-next';
import VueOfficeDocx from '@vue-office/docx';
import VueOfficeExcel from '@vue-office/excel';
import VueOfficePptx from '@vue-office/pptx';
import '@vue-office/docx/lib/index.css';
import '@vue-office/excel/lib/index.css';
import '@vue-office/pptx/lib/index.css';

interface Props {
  file: {
    id: number;
    name: string;
    url: string;
    download_url?: string | null;
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

const getExtension = (name: string) => {
  const parts = name.toLowerCase().split('.');
  return parts.length > 1 ? parts.pop() : '';
};

const fileType = computed(() => {
  const type = props.file.type || '';
  const ext = getExtension(props.file.name || '');
  if (type.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) return 'image';
  if (type === 'application/pdf' || ext === 'pdf') return 'pdf';
  if (type.startsWith('video/') || ['mp4', 'webm', 'ogg', 'mov', 'm4v'].includes(ext)) return 'video';
  if (ext === 'docx') return 'docx';
  if (ext === 'xlsx') return 'xlsx';
  if (ext === 'pptx') return 'pptx';
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
  const url = props.file.download_url || props.file.url;
  window.open(url, '_blank');
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

          <!-- Office Previews -->
          <div v-else-if="fileType === 'docx'" class="bg-background rounded-lg border p-3">
            <VueOfficeDocx :src="file.url" class="min-h-[70vh]" />
          </div>
          <div v-else-if="fileType === 'xlsx'" class="bg-background rounded-lg border p-3">
            <VueOfficeExcel :src="file.url" class="min-h-[70vh]" />
          </div>
          <div v-else-if="fileType === 'pptx'" class="bg-background rounded-lg border p-3">
            <VueOfficePptx :src="file.url" class="min-h-[70vh]" />
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
