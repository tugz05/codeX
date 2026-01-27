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
    <DialogContent class="w-[96vw] max-w-6xl h-[92vh] sm:h-[90vh] p-0">
      <div class="relative flex h-full flex-col bg-background">
        <!-- Header -->
        <div class="border-b bg-gradient-to-b from-background to-muted/30">
          <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
            <div class="flex items-center gap-3 min-w-0">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg border bg-card shadow-sm">
                <component :is="fileIcon" class="h-5 w-5 text-muted-foreground" />
              </div>
              <div class="min-w-0">
                <h3 class="font-semibold truncate text-base sm:text-lg">{{ file.name }}</h3>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                  <span>{{ formatFileSize(file.size) }}</span>
                  <span class="text-muted-foreground/60">•</span>
                  <span class="capitalize">{{ fileType }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" class="h-9 px-3" @click="handleDownload">
                <Download class="h-4 w-4 mr-2" />
                <span class="hidden sm:inline">Download</span>
              </Button>
              <Button variant="ghost" size="icon" class="h-9 w-9" @click="isOpen = false">
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Preview Content -->
        <div class="flex-1 overflow-auto bg-muted/20 p-3 sm:p-4">
          <!-- Image Preview -->
          <div v-if="fileType === 'image'" class="flex items-center justify-center h-full">
            <img
              :src="file.url"
              :alt="file.name"
              class="max-w-full max-h-[78vh] object-contain rounded-xl border bg-card shadow-md"
            />
          </div>

          <!-- PDF Preview -->
          <div v-else-if="fileType === 'pdf'" class="flex items-center justify-center h-full">
            <iframe
              :src="file.url"
              class="w-full h-[80vh] rounded-xl border bg-background shadow-sm"
              frameborder="0"
            ></iframe>
          </div>

          <!-- Video Preview -->
          <div v-else-if="fileType === 'video'" class="flex items-center justify-center h-full">
            <video
              :src="file.url"
              controls
              class="max-w-full max-h-[78vh] rounded-xl border bg-card shadow-md"
            >
              Your browser does not support the video tag.
            </video>
          </div>

          <!-- Office Previews -->
          <div v-else-if="fileType === 'docx'" class="rounded-xl border bg-background p-3 shadow-sm">
            <VueOfficeDocx :src="file.url" class="min-h-[80vh]" />
          </div>
          <div v-else-if="fileType === 'xlsx'" class="rounded-xl border bg-background p-3 shadow-sm">
            <VueOfficeExcel :src="file.url" class="min-h-[80vh]" />
          </div>
          <div v-else-if="fileType === 'pptx'" class="rounded-xl border bg-background p-3 shadow-sm">
            <VueOfficePptx :src="file.url" class="min-h-[80vh]" />
          </div>

          <!-- Other Files -->
          <div v-else class="flex flex-col items-center justify-center h-full text-center p-8">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border bg-card shadow-sm mb-4">
              <component :is="fileIcon" class="h-10 w-10 text-muted-foreground" />
            </div>
            <p class="text-lg font-semibold mb-2">{{ file.name }}</p>
            <p class="text-sm text-muted-foreground mb-5">{{ formatFileSize(file.size) }}</p>
            <Button class="h-9 px-4" @click="handleDownload">
              <Download class="h-4 w-4 mr-2" />
              Download File
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
