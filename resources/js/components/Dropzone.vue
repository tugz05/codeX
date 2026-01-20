<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils' // optional; remove if you don’t have it
import { UploadCloud, X } from 'lucide-vue-next'

/**
 * Props
 * - modelValue: File[]
 * - accept: string[] (mime or extensions like 'image/*','video/mp4','.pdf')
 * - maxSizeMB: number (per file)
 * - multiple: boolean
 */
const props = withDefaults(defineProps<{
  modelValue: File[]
  accept?: string[]
  maxSizeMB?: number
  multiple?: boolean
}>(), {
  accept: () => ['image/jpeg','image/png','image/gif','image/webp','video/mp4','application/pdf'],
  maxSizeMB: 50,
  multiple: true
})

const emit = defineEmits<{ (e:'update:modelValue', v:File[]): void }>()

const isDragging = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

function openPicker() {
  inputRef.value?.click()
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}
function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
}
function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  if (!e.dataTransfer) return
  handleFiles(e.dataTransfer.files)
}
function onInput(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files) handleFiles(input.files)
}

function handleFiles(fileList: FileList) {
  const files = Array.from(fileList)
  const filtered: File[] = []
  const maxBytes = props.maxSizeMB * 1024 * 1024

  files.forEach(f => {
    const typeOk = props.accept.length
      ? props.accept.some(a => {
          // allow 'image/*'
          if (a.endsWith('/*')) return f.type.startsWith(a.replace('/*','/'))
          // allow extension like '.pdf'
          if (a.startsWith('.')) return f.name.toLowerCase().endsWith(a.toLowerCase())
          // exact mime
          return f.type === a
        })
      : true

    const sizeOk = f.size <= maxBytes

    if (typeOk && sizeOk) {
      filtered.push(f)
    } else {
      // You can show a toast here if you want (left simple to keep component pure)
      // e.g. toast.error(`"${f.name}" is not allowed or exceeds ${props.maxSizeMB}MB.`)
    }
  })

  const merged = props.multiple ? [...props.modelValue, ...filtered] : filtered.slice(0, 1)
  emit('update:modelValue', dedupe(merged))
}

function removeAt(idx: number) {
  const next = [...props.modelValue]
  next.splice(idx, 1)
  emit('update:modelValue', next)
}

function dedupe(arr: File[]) {
  // dedupe by name + size + lastModified (best effort)
  const map = new Map<string, File>()
  arr.forEach(f => map.set(`${f.name}|${f.size}|${f.lastModified}`, f))
  return Array.from(map.values())
}

const acceptAttr = computed(() => props.accept.join(','))
</script>

<template>
  <div class="space-y-3">
    <!-- Drop area -->
    <div
      class="rounded-xl border-2 border-dashed p-6 text-center transition-colors"
      :class="isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/30 hover:bg-muted/30'"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      role="button"
      tabindex="0"
      @keydown.enter.prevent="openPicker"
      @keydown.space.prevent="openPicker"
    >
      <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
        <UploadCloud class="h-5 w-5 text-muted-foreground" />
      </div>

      <p class="text-sm font-medium">Choose a file or drag & drop it here</p>
      <p class="mt-1 text-xs text-muted-foreground">
        JPEG, PNG, PDF, and MP4 formats, up to {{ maxSizeMB }}MB
      </p>

      <div class="mt-4">
        <Button type="button" size="sm" variant="secondary" @click="openPicker">
          Browse File
        </Button>
      </div>

      <!-- hidden picker -->
      <input
        ref="inputRef"
        type="file"
        class="hidden"
        :accept="acceptAttr"
        :multiple="multiple"
        @change="onInput"
      />
    </div>

    <!-- Selected files list -->
    <div v-if="modelValue?.length" class="rounded-lg border p-3">
      <p class="mb-2 text-xs font-medium text-muted-foreground">Selected file(s)</p>
      <ul class="space-y-2">
        <li v-for="(f, i) in modelValue" :key="`${f.name}-${f.size}-${f.lastModified}-${i}`"
            class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2 text-sm">
          <span class="truncate">{{ f.name }}</span>
          <button type="button" class="text-muted-foreground hover:text-foreground" @click="removeAt(i)" aria-label="Remove file">
            <X class="h-4 w-4" />
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
