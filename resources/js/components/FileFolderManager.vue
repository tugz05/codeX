<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Folder, FolderPlus, File, Trash2, Edit, ChevronRight, ChevronDown } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

interface FileItem {
  id: number;
  name: string;
  type: string | null;
  url: string;
  size: number | null;
  folder_id: number | null;
  submission_id?: number;
}

interface FolderItem {
  id: number;
  name: string;
  description: string | null;
  parent_id: number | null;
  children?: FolderItem[];
  files?: FileItem[];
}

interface Props {
  folders: FolderItem[];
  files: FileItem[];
  currentFolderId?: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'folder-selected': [folderId: number | null];
  'file-moved': [fileId: number, folderId: number | null];
  'folder-created': [folder: FolderItem];
  'folder-deleted': [folderId: number];
}>();

const showCreateFolder = ref(false);
const showEditFolder = ref(false);
const editingFolder = ref<FolderItem | null>(null);
const expandedFolders = ref<Set<number>>(new Set());

// Get submission ID from files
const submissionId = computed(() => {
  if (props.files.length > 0 && props.files[0].submission_id) {
    return props.files[0].submission_id;
  }
  return null;
});

const createFolderForm = useForm({
  name: '',
  description: '',
  parent_id: props.currentFolderId || null,
  folderable_type: 'AssignmentSubmission',
  folderable_id: submissionId.value,
});

const editFolderForm = useForm({
  name: '',
  description: '',
});

const openCreateFolder = (parentId: number | null = null) => {
  createFolderForm.reset();
  createFolderForm.parent_id = parentId || props.currentFolderId || null;
  showCreateFolder.value = true;
};

const openEditFolder = (folder: FolderItem) => {
  editingFolder.value = folder;
  editFolderForm.name = folder.name;
  editFolderForm.description = folder.description || '';
  showEditFolder.value = true;
};

const toggleFolder = (folderId: number) => {
  if (expandedFolders.value.has(folderId)) {
    expandedFolders.value.delete(folderId);
  } else {
    expandedFolders.value.add(folderId);
  }
};

const selectFolder = (folderId: number | null) => {
  emit('folder-selected', folderId);
};

const rootFolders = computed(() => {
  return props.folders.filter(f => !f.parent_id);
});

const getFolderChildren = (parentId: number) => {
  return props.folders.filter(f => f.parent_id === parentId);
};

const getFolderFiles = (folderId: number) => {
  return props.files.filter(f => f.folder_id === folderId);
};

const rootFiles = computed(() => {
  return props.files.filter(f => !f.folder_id);
});

const createFolder = () => {
  // Update folderable_id before submitting
  createFolderForm.folderable_id = submissionId.value;
  
  createFolderForm.post(route('student.files.folders.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateFolder.value = false;
      createFolderForm.reset();
      createFolderForm.folderable_id = submissionId.value;
      // Reload to get updated folders
      window.location.reload();
    },
  });
};

const updateFolder = () => {
  if (!editingFolder.value) return;
  
  editFolderForm.put(route('student.files.folders.update', editingFolder.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditFolder.value = false;
      editingFolder.value = null;
      editFolderForm.reset();
    },
  });
};

const deleteFolder = (folderId: number) => {
  if (!confirm('Are you sure you want to delete this folder? Files will be moved to root.')) return;
  
  useForm({}).delete(route('student.files.folders.destroy', folderId), {
    preserveScroll: true,
    onSuccess: () => {
      emit('folder-deleted', folderId);
    },
  });
};

const renderFolderTree = (folders: FolderItem[], level = 0) => {
  return folders.map(folder => {
    const isExpanded = expandedFolders.value.has(folder.id);
    const children = getFolderChildren(folder.id);
    const files = getFolderFiles(folder.id);
    
    return {
      folder,
      level,
      isExpanded,
      children: children.length > 0 ? renderFolderTree(children, level + 1) : [],
      files,
    };
  });
};

const folderTree = computed(() => renderFolderTree(rootFolders.value));
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold">File Organization</h3>
      <Button variant="outline" size="sm" @click="openCreateFolder()">
        <FolderPlus class="h-4 w-4 mr-2" />
        New Folder
      </Button>
    </div>

    <!-- Folder Tree -->
    <div class="space-y-1">
      <!-- Root Files -->
      <div
        v-if="rootFiles.length > 0"
        class="flex items-center gap-2 p-2 rounded hover:bg-accent/50 cursor-pointer"
        :class="{ 'bg-accent': currentFolderId === null }"
        @click="selectFolder(null)"
      >
        <File class="h-4 w-4 text-muted-foreground" />
        <span class="text-sm">Root Files ({{ rootFiles.length }})</span>
      </div>

      <!-- Folders -->
      <template v-for="item in folderTree" :key="item.folder.id">
        <div class="space-y-1">
          <div
            class="flex items-center gap-2 p-2 rounded hover:bg-accent/50 cursor-pointer"
            :class="{ 'bg-accent': currentFolderId === item.folder.id }"
            :style="{ paddingLeft: `${item.level * 1.5 + 0.5}rem` }"
          >
            <Button
              variant="ghost"
              size="icon"
              class="h-6 w-6"
              @click.stop="toggleFolder(item.folder.id)"
            >
              <ChevronRight v-if="!item.isExpanded && item.children.length > 0" class="h-4 w-4" />
              <ChevronDown v-else-if="item.isExpanded && item.children.length > 0" class="h-4 w-4" />
              <span v-else class="w-4"></span>
            </Button>
            <Folder class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm flex-1" @click="selectFolder(item.folder.id)">
              {{ item.folder.name }}
              <span v-if="item.files.length > 0" class="text-muted-foreground">
                ({{ item.files.length }})
              </span>
            </span>
            <div class="flex items-center gap-1">
              <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                @click.stop="openEditFolder(item.folder)"
              >
                <Edit class="h-3 w-3" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6 text-destructive"
                @click.stop="deleteFolder(item.folder.id)"
              >
                <Trash2 class="h-3 w-3" />
              </Button>
            </div>
          </div>

          <!-- Nested Folders -->
          <template v-if="item.isExpanded">
            <div
              v-for="child in item.children"
              :key="child.folder.id"
              class="flex items-center gap-2 p-2 rounded hover:bg-accent/50 cursor-pointer"
              :class="{ 'bg-accent': currentFolderId === child.folder.id }"
              :style="{ paddingLeft: `${child.level * 1.5 + 0.5}rem` }"
            >
              <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                @click.stop="toggleFolder(child.folder.id)"
              >
                <ChevronRight v-if="!child.isExpanded && child.children.length > 0" class="h-4 w-4" />
                <ChevronDown v-else-if="child.isExpanded && child.children.length > 0" class="h-4 w-4" />
                <span v-else class="w-4"></span>
              </Button>
              <Folder class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm flex-1" @click="selectFolder(child.folder.id)">
                {{ child.folder.name }}
                <span v-if="child.files.length > 0" class="text-muted-foreground">
                  ({{ child.files.length }})
                </span>
              </span>
              <div class="flex items-center gap-1">
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6"
                  @click.stop="openEditFolder(child.folder)"
                >
                  <Edit class="h-3 w-3" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6 text-destructive"
                  @click.stop="deleteFolder(child.folder.id)"
                >
                  <Trash2 class="h-3 w-3" />
                </Button>
              </div>
            </div>
          </template>
        </div>
      </template>
    </div>

    <!-- Create Folder Dialog -->
    <Dialog v-model:open="showCreateFolder">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create Folder</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="createFolder" class="space-y-4">
          <div>
            <Label for="folder-name">Folder Name</Label>
            <Input
              id="folder-name"
              v-model="createFolderForm.name"
              placeholder="Enter folder name"
              required
            />
          </div>
          <div>
            <Label for="folder-description">Description (Optional)</Label>
            <Input
              id="folder-description"
              v-model="createFolderForm.description"
              placeholder="Enter description"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showCreateFolder = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="createFolderForm.processing">
              Create
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Folder Dialog -->
    <Dialog v-model:open="showEditFolder">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Folder</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="updateFolder" class="space-y-4">
          <div>
            <Label for="edit-folder-name">Folder Name</Label>
            <Input
              id="edit-folder-name"
              v-model="editFolderForm.name"
              placeholder="Enter folder name"
              required
            />
          </div>
          <div>
            <Label for="edit-folder-description">Description (Optional)</Label>
            <Input
              id="edit-folder-description"
              v-model="editFolderForm.description"
              placeholder="Enter description"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showEditFolder = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="editFolderForm.processing">
              Update
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
