<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Download, FileText, Image, Video, File } from 'lucide-vue-next';
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
    type?: string | null;
    size?: number | null;
  } | null;
}

const props = defineProps<Props>();

const getExtension = (name: string) => {
  const parts = name.toLowerCase().split('.');
  return parts.length > 1 ? parts.pop() : '';
};

const fileType = computed(() => {
  if (!props.file) return 'none';
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

const formatFileSize = (bytes: number | null | undefined): string => {
  if (!bytes) return 'Unknown size';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const handleDownload = () => {
  if (!props.file) return;
  const url = props.file.download_url || props.file.url;
  window.open(url, '_blank');
};
</script>

<template>
  <div class="h-full flex flex-col bg-background border rounded-lg overflow-hidden">
    <div v-if="!file" class="flex items-center justify-center h-full text-muted-foreground">
      <div class="text-center p-6">
        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
        <p class="text-sm">Select a file to preview</p>
      </div>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="border-b bg-gradient-to-b from-background to-muted/30 p-2">
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2 min-w-0">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg border bg-card shadow-sm shrink-0">
              <component :is="fileIcon" class="h-3.5 w-3.5 text-muted-foreground" />
            </div>
            <div class="min-w-0">
              <h3 class="font-semibold truncate text-sm">{{ file.name }}</h3>
              <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span>{{ formatFileSize(file.size) }}</span>
                <span class="text-muted-foreground/60">•</span>
                <span class="capitalize">{{ fileType }}</span>
              </div>
            </div>
          </div>
          <Button variant="outline" size="sm" class="h-7 px-2 shrink-0" @click="handleDownload">
            <Download class="h-3 w-3" />
          </Button>
        </div>
      </div>

      <!-- Preview Content -->
      <div class="flex-1 overflow-auto bg-muted/20 p-2">
        <!-- Image Preview -->
        <div v-if="fileType === 'image'" class="flex items-center justify-center h-full w-full">
          <img
            :src="file.url"
            :alt="file.name"
            class="max-w-full max-h-full object-contain rounded-lg border bg-card shadow-md"
          />
        </div>

        <!-- PDF Preview -->
        <div v-else-if="fileType === 'pdf'" class="h-full w-full">
          <iframe
            :src="file.url"
            class="w-full h-full rounded-lg border-0 bg-background"
            frameborder="0"
          ></iframe>
        </div>

        <!-- Video Preview -->
        <div v-else-if="fileType === 'video'" class="flex items-center justify-center h-full w-full">
          <video
            :src="file.url"
            controls
            class="max-w-full max-h-full rounded-lg border bg-card shadow-md"
          >
            Your browser does not support the video tag.
          </video>
        </div>

        <!-- Office Previews -->
        <div v-else-if="fileType === 'docx'" class="h-full w-full rounded-lg border bg-background p-3 shadow-sm overflow-auto">
          <VueOfficeDocx :src="file.url" style="min-height: 100%; width: 100%;" />
        </div>
        <div v-else-if="fileType === 'xlsx'" class="h-full w-full rounded-lg border bg-background p-3 shadow-sm overflow-auto">
          <VueOfficeExcel :src="file.url" style="min-height: 100%; width: 100%;" />
        </div>
        <div v-else-if="fileType === 'pptx'" class="h-full w-full rounded-lg border bg-background p-3 shadow-sm overflow-auto">
          <VueOfficePptx :src="file.url" style="min-height: 100%; width: 100%;" />
        </div>

        <!-- Other Files -->
        <div v-else class="flex flex-col items-center justify-center h-full text-center p-6">
          <div class="flex h-16 w-16 items-center justify-center rounded-2xl border bg-card shadow-sm mb-3">
            <component :is="fileIcon" class="h-8 w-8 text-muted-foreground" />
          </div>
          <p class="text-sm font-semibold mb-1">{{ file.name }}</p>
          <p class="text-xs text-muted-foreground mb-4">{{ formatFileSize(file.size) }}</p>
          <Button size="sm" class="h-8 px-3" @click="handleDownload">
            <Download class="h-3 w-3 mr-2" />
            Download File
          </Button>
        </div>
      </div>
    </template>
  </div>
</template>
