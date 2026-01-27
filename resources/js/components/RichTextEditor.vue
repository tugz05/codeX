<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Bold, Italic, List, ListOrdered, Highlighter } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    modelValue: string | null
    placeholder?: string
    minHeight?: string
  }>(),
  {
    modelValue: '',
    placeholder: 'Write here...',
    minHeight: '140px',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const editorRef = ref<HTMLDivElement | null>(null)
const content = ref(props.modelValue ?? '')

const syncEditor = (value: string) => {
  content.value = value
  if (editorRef.value && editorRef.value.innerHTML !== value) {
    editorRef.value.innerHTML = value || ''
  }
}

const onInput = () => {
  const html = editorRef.value?.innerHTML ?? ''
  content.value = html
  emit('update:modelValue', html)
}

const runCommand = (command: string, value?: string) => {
  editorRef.value?.focus()
  document.execCommand(command, false, value)
  onInput()
}

const toggleHighlight = () => {
  editorRef.value?.focus()
  document.execCommand('hiliteColor', false, '#fff3a3')
  document.execCommand('backColor', false, '#fff3a3')
  onInput()
}

watch(
  () => props.modelValue,
  (value) => syncEditor(value ?? ''),
)

onMounted(() => {
  syncEditor(props.modelValue ?? '')
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/40 p-2">
      <Button type="button" variant="ghost" size="sm" class="h-8 px-2" @click="runCommand('bold')">
        <Bold class="h-4 w-4" />
      </Button>
      <Button type="button" variant="ghost" size="sm" class="h-8 px-2" @click="runCommand('italic')">
        <Italic class="h-4 w-4" />
      </Button>
      <div class="mx-1 h-5 w-px bg-border" />
      <Button type="button" variant="ghost" size="sm" class="h-8 px-2" @click="runCommand('insertUnorderedList')">
        <List class="h-4 w-4" />
      </Button>
      <Button type="button" variant="ghost" size="sm" class="h-8 px-2" @click="runCommand('insertOrderedList')">
        <ListOrdered class="h-4 w-4" />
      </Button>
      <div class="mx-1 h-5 w-px bg-border" />
      <Button type="button" variant="ghost" size="sm" class="h-8 px-2" @click="toggleHighlight">
        <Highlighter class="h-4 w-4" />
      </Button>
    </div>
    <div
      ref="editorRef"
      class="rich-editor rounded-lg border bg-background p-3 text-sm leading-6 text-foreground focus-visible:outline-none"
      :style="{ minHeight: props.minHeight }"
      :data-placeholder="props.placeholder"
      contenteditable="true"
      spellcheck="true"
      @input="onInput"
      @blur="onInput"
    />
  </div>
</template>

<style scoped>
.rich-editor:empty:before {
  content: attr(data-placeholder);
  color: hsl(var(--muted-foreground));
}
.rich-editor:focus {
  outline: none;
  box-shadow: 0 0 0 2px hsl(var(--ring));
}
</style>
