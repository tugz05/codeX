<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { FileText, Image, Video, File, Eye, Download, History, Folder, MoreVertical } from 'lucide-vue-next';
import FilePreview from './FilePreview.vue';
import FileVersionHistory from './FileVersionHistory.vue';
import FileVersionUpload from './FileVersionUpload.vue';
import { useForm } from '@inertiajs/vue3';

interface FileItem {
  id: number;
  name: string;
  url: string;
  type: string | null;
  size: number | null;
  version?: number;
  is_current?: boolean;
  folder_id?: number | null;
  versions?: FileItem[];
}

interface Props {
  files: FileItem[];
  submissionId?: number;
  canEdit?: boolean;
  folders?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
  folders: () => [],
});

const emit = defineEmits<{
  'file-removed': [fileId: number];
  'file-moved': [fileId: number, folderId: number | null];
}>();

const previewFile = ref<FileItem | null>(null);
const showPreview = ref(false);
const showVersionHistory = ref(false);
const versionHistoryFile = ref<FileItem | null>(null);
const showVersionUpload = ref(false);
const uploadingFile = ref<FileItem | null>(null);
const showMoveDialog = ref(false);
const movingFile = ref<FileItem | null>(null);

const moveForm = useForm({
  folder_id: null as number | null,
});

const getFileIcon = (type: string | null) => {
  if (!type) return File;
  if (type.startsWith('image/')) return Image;
  if (type === 'application/pdf') return FileText;
  if (type.startsWith('video/')) return Video;
  return File;
};

const formatFileSize = (bytes: number | null): string => {
  if (!bytes) return 'Unknown size';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const openPreview = (file: FileItem) => {
  previewFile.value = file;
  showPreview.value = true;
};

const openVersionHistory = (file: FileItem) => {
  versionHistoryFile.value = file;
  showVersionHistory.value = true;
};

const openVersionUpload = (file: FileItem) => {
  uploadingFile.value = file;
  showVersionUpload.value = true;
};

const onVersionUploaded = () => {
  // Reload page to get updated versions
  window.location.reload();
};

const openMoveDialog = (file: FileItem) => {
  movingFile.value = file;
  moveForm.folder_id = file.folder_id || null;
  showMoveDialog.value = true;
};

const moveFile = () => {
  if (!movingFile.value) return;
  
  moveForm.post(route('student.files.folders.move-file'), {
    data: {
      file_id: movingFile.value.id,
      folder_id: moveForm.folder_id,
      file_type: 'assignment_submission',
    },
    preserveScroll: true,
    onSuccess: () => {
      showMoveDialog.value = false;
      emit('file-moved', movingFile.value!.id, moveForm.folder_id);
      movingFile.value = null;
      moveForm.reset();
    },
  });
};

const handleDownload = (url: string) => {
  window.open(url, '_blank');
};

const removeFile = (fileId: number) => {
  emit('file-removed', fileId);
};
</script>

<template>
  <div class="space-y-2">
    <div
      v-for="file in files"
      :key="file.id"
      class="flex items-center justify-between rounded-lg border bg-card p-4 transition-all hover:bg-accent/50 hover:shadow-sm"
    >
      <div class="flex items-center gap-3 min-w-0 flex-1">
        <component :is="getFileIcon(file.type)" class="h-5 w-5 text-muted-foreground shrink-0" />
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <p class="text-sm font-medium truncate">{{ file.name }}</p>
            <Badge v-if="file.version && file.version > 1" variant="outline" class="text-xs">
              v{{ file.version }}
            </Badge>
            <Badge v-if="file.is_current === false" variant="secondary" class="text-xs">
              Old Version
            </Badge>
          </div>
          <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <Button variant="ghost" size="icon" @click="openPreview(file)">
          <Eye class="h-4 w-4" />
        </Button>
        <Button variant="ghost" size="icon" @click="handleDownload(file.url)">
          <Download class="h-4 w-4" />
        </Button>
        <DropdownMenu v-if="canEdit">
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon">
              <MoreVertical class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="openVersionHistory(file)">
              <History class="h-4 w-4 mr-2" />
              Version History
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canEdit" @click="openVersionUpload(file)">
              <Upload class="h-4 w-4 mr-2" />
              Upload New Version
            </DropdownMenuItem>
            <DropdownMenuItem @click="openMoveDialog(file)">
              <Folder class="h-4 w-4 mr-2" />
              Move to Folder
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canEdit" @click="removeFile(file.id)" class="text-destructive">
              Remove
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  </div>

  <!-- File Preview -->
  <FilePreview
    v-if="previewFile"
    :file="previewFile"
    v-model:open="showPreview"
  />

  <!-- Version History -->
  <FileVersionHistory
    v-if="versionHistoryFile"
    :versions="versionHistoryFile.versions || [versionHistoryFile]"
    :current-version="versionHistoryFile"
    v-model:open="showVersionHistory"
  />

  <!-- Version Upload -->
  <FileVersionUpload
    v-if="uploadingFile"
    :file-id="uploadingFile.id"
    v-model:open="showVersionUpload"
    @version-uploaded="onVersionUploaded"
  />

  <!-- Move File Dialog -->
  <Dialog v-model:open="showMoveDialog">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Move File to Folder</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="moveFile" class="space-y-4">
        <div>
          <Label>Select Folder</Label>
          <Select v-model="moveForm.folder_id">
            <SelectTrigger>
              <SelectValue placeholder="Root (No folder)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">Root (No folder)</SelectItem>
              <SelectItem
                v-for="folder in folders"
                :key="folder.id"
                :value="folder.id"
              >
                {{ folder.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="showMoveDialog = false">
            Cancel
          </Button>
          <Button type="submit" :disabled="moveForm.processing">
            Move
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
