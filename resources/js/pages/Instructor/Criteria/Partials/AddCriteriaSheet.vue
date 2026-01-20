<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter, SheetClose } from '@/components/ui/sheet'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Plus, Trash2, Save, X, Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = defineProps<{ open:boolean }>()
const emit = defineEmits<{ (e:'update:open', v:boolean):void }>()

type Item = { id?:number; label:string; description?:string; points?:number; weight?:number; sort_order?:number }

const form = useForm({
  title: '',
  description: '',
  grading_method: 'sum' as 'sum'|'average'|'custom',
  items: [] as Item[],
})

const processing = computed(() => form.processing)

const addItem = () => {
  form.items.push({ label: '', description: '', points: 0, weight: 0, sort_order: form.items.length })
}
const removeItem = (idx:number) => { form.items.splice(idx,1) }

function submit() {
  form.post(route('instructor.criteria.store'), {
    onSuccess: () => { toast.success('Criteria created.'); emit('update:open', false); form.reset(); form.items = [] },
    onError: () => toast.error('Create failed.'),
  })
}
</script>

<template>
  <Sheet :open="props.open" @update:open="emit('update:open',$event)">
    <SheetContent class="sm:max-w-2xl !p-0">
      <div class="flex h-full flex-col">
        <div class="px-6 pt-6">
          <SheetHeader class="text-left">
            <SheetTitle>New Criteria</SheetTitle>
            <SheetDescription>Define a reusable rubric/criteria for activities.</SheetDescription>
          </SheetHeader>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-6">
          <form @submit.prevent="submit" class="mt-4 space-y-5">
            <div class="space-y-2">
              <Label for="title">Title</Label>
              <Input id="title" v-model="form.title" placeholder="e.g., Programming Rubric" required />
              <Alert v-if="form.errors.title" variant="destructive" class="mt-2">
                <AlertTitle>Error</AlertTitle><AlertDescription>{{ form.errors.title }}</AlertDescription>
              </Alert>
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="form.description" rows="3" placeholder="Optional description…" />
            </div>

              <div class="space-y-2">
                <Label>Grading method</Label>
                <Select v-model="form.grading_method">
                  <SelectTrigger><SelectValue placeholder="Select method" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="sum">Sum (points)</SelectItem>
                    <SelectItem value="average">Average (weights %)</SelectItem>
                    <SelectItem value="custom">Custom (weights %)</SelectItem>
                  </SelectContent>
                </Select>
              </div>            <div class="rounded-xl border p-3">
              <div class="mb-2 flex items-center justify-between">
                <div class="text-sm font-medium">Items</div>
                <Button size="sm" variant="secondary" @click.prevent="addItem"><Plus class="mr-1 h-4 w-4" /> Add item</Button>
              </div>

              <div v-if="!form.items.length" class="text-xs text-muted-foreground">No items yet.</div>

              <div class="space-y-3">
                <div v-for="(it, idx) in form.items" :key="idx" class="rounded-lg border p-3">
                  <div class="grid gap-3 sm:grid-cols-12">
                    <div class="sm:col-span-8 space-y-2">
                      <Label>Label</Label>
                      <Input v-model="it.label" placeholder="e.g., Correctness" />
                    </div>
                    <div v-if="form.grading_method==='sum'" class="sm:col-span-2 space-y-2">
                      <Label>Points</Label>
                      <Input type="number" min="0" v-model.number="it.points" />
                    </div>
                    <div v-else class="sm:col-span-2 space-y-2">
                      <Label>Weight (%)</Label>
                      <Input type="number" min="0" max="100" step="0.1" v-model.number="it.weight" />
                    </div>
                    <div class="sm:col-span-2 flex items-end justify-end">
                      <Button variant="outline" size="icon" @click.prevent="removeItem(idx)" title="Remove">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <div class="mt-2">
                    <Label>Description</Label>
                    <Textarea v-model="it.description" rows="2" placeholder="Optional details…" />
                  </div>
                </div>
              </div>

              <Alert v-if="form.errors.items" variant="destructive" class="mt-3">
                <AlertTitle>Error</AlertTitle><AlertDescription>{{ form.errors.items }}</AlertDescription>
              </Alert>
            </div>
          </form>
        </div>

        <div class="sticky bottom-0 border-t bg-background/80 backdrop-blur">
          <div class="px-6 py-4">
            <SheetFooter class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <SheetClose as-child>
                <Button type="button" variant="outline" :disabled="processing"><X class="mr-2 h-4 w-4" /> Close</Button>
              </SheetClose>
              <Button type="submit" :disabled="processing" @click="submit">
                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ processing ? 'Saving…' : 'Create' }}
              </Button>
            </SheetFooter>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>
