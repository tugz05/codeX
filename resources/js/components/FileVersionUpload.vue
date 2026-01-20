<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Upload, X } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

interface Props {
  fileId: number;
  open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'version-uploaded': [];
}>();

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const versionForm = useForm({
  file: null as File | null,
  version_notes: '',
});

const openFilePicker = () => {
  fileInputRef.value?.click();
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0];
    versionForm.file = target.files[0];
  }
};

const removeFile = () => {
  selectedFile.value = null;
  versionForm.file = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const uploadVersion = () => {
  if (!versionForm.file) return;

  versionForm.post(route('student.files.versions.store', props.fileId), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isOpen.value = false;
      versionForm.reset();
      selectedFile.value = null;
      emit('version-uploaded');
    },
  });
};

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Upload New Version</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="uploadVersion" class="space-y-4">
        <div>
          <Label>Select File</Label>
          <div
            class="mt-2 rounded-lg border-2 border-dashed p-4 text-center cursor-pointer hover:bg-accent/50 transition-colors"
            @click="openFilePicker"
          >
            <input
              ref="fileInputRef"
              type="file"
              @change="handleFileChange"
              class="hidden"
              accept="*/*"
            />
            <Upload class="mx-auto h-8 w-8 text-muted-foreground mb-2" />
            <p class="text-sm font-medium">Click to select file</p>
            <p class="text-xs text-muted-foreground mt-1">Up to 50MB</p>
          </div>
          <div v-if="selectedFile" class="mt-2 flex items-center justify-between p-3 bg-muted/50 rounded-lg">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ selectedFile.name }}</p>
              <p class="text-xs text-muted-foreground">{{ formatFileSize(selectedFile.size) }}</p>
            </div>
            <Button type="button" variant="ghost" size="icon" @click="removeFile">
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>
        <div>
          <Label for="version_notes">Version Notes (Optional)</Label>
          <Textarea
            id="version_notes"
            v-model="versionForm.version_notes"
            placeholder="Describe what changed in this version..."
            rows="3"
          />
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="isOpen = false">
            Cancel
          </Button>
          <Button type="submit" :disabled="versionForm.processing || !selectedFile">
            <Upload class="h-4 w-4 mr-2" />
            {{ versionForm.processing ? 'Uploading...' : 'Upload Version' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
