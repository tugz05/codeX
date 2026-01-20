<script setup lang="ts">
import AppLayout from '@/layouts/AuthLayoutInstructor.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { toast } from 'vue-sonner'
import { Plus, MoreVertical } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import AddCriteriaSheet from './Partials/AddCriteriaSheet.vue'
import EditCriteriaSheet from './Partials/EditCriteriaSheet.vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

const props = defineProps<{
  criteria: Array<{
    id:number
    title:string
    description:string|null
    grading_method:'sum'|'average'|'custom'
    items:Array<{ id:number; label:string; points:number|null; weight:number|null; sort_order:number }>
  }>
}>()

const showAdd = ref(false)
const showEdit = ref(false)
const selected = ref<typeof props.criteria[number] | null>(null)

const showDelete = ref(false)
const deleteId = ref<number | null>(null)
const confirmDelete = (id:number) => { deleteId.value = id; showDelete.value = true }
const doDelete = () => {
  if (!deleteId.value) return
  router.delete(route('instructor.criteria.destroy', deleteId.value), {
    onSuccess: () => { toast.success('Criteria deleted.'); showDelete.value = false; deleteId.value = null },
    onError: () => toast.error('Delete failed.')
  })
}

const openEdit = (row:any) => { selected.value = row; showEdit.value = true }
</script>

<template>
  <Head title="Criteria" />
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Criteria</h1>
        <Button @click="showAdd = true"><Plus class="mr-2 h-4 w-4" /> New Criteria</Button>
      </div>

      <!-- list -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="c in props.criteria" :key="c.id"
             class="rounded-xl border bg-card p-4 shadow-sm">
          <div class="mb-2 flex items-start justify-between gap-2">
            <div>
              <div class="text-base font-semibold">{{ c.title }}</div>
              <div class="text-xs text-muted-foreground">Method: {{ c.grading_method.toUpperCase() }}</div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <button class="rounded p-1 hover:bg-accent"><MoreVertical class="h-4 w-4" /></button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem @click="openEdit(c)">Edit</DropdownMenuItem>
                <DropdownMenuItem @click="confirmDelete(c.id)">
                  <span class="text-red-500">Delete</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <p v-if="c.description" class="mb-3 line-clamp-3 text-sm text-muted-foreground">{{ c.description }}</p>

          <div class="space-y-1">
            <div v-for="i in c.items" :key="i.id" class="flex items-center justify-between rounded-md border px-2 py-1 text-xs">
              <span class="truncate">{{ i.label }}</span>
              <span class="text-muted-foreground">
                <template v-if="c.grading_method==='sum'">{{ i.points ?? 0 }} pts</template>
                <template v-else>{{ i.weight ?? 0 }}%</template>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add / Edit Sheets -->
    <AddCriteriaSheet v-model:open="showAdd" />
    <EditCriteriaSheet v-if="selected" v-model:open="showEdit" :criteria="selected" @closed="selected = null" />

    <!-- Delete confirm -->
    <Dialog v-model:open="showDelete">
      <DialogContent class="sm:max-w-md">
        <DialogHeader><DialogTitle>Delete criteria?</DialogTitle></DialogHeader>
        <p class="text-sm text-muted-foreground">This action cannot be undone.</p>
        <DialogFooter class="gap-2 sm:justify-end">
          <Button variant="outline" @click="showDelete = false">Cancel</Button>
          <Button variant="destructive" @click="doDelete">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
