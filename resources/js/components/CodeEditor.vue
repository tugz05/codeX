<script setup lang="ts">
import { ref, watch } from 'vue'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'

const props = withDefaults(defineProps<{
  modelValue: string
  language: 'python' | 'java' | 'cpp'
  height?: string
}>(), {
  height: '82vh'
})

const emit = defineEmits<{ (e:'update:modelValue', v:string): void }>()

const code = ref(props.modelValue)
watch(() => props.modelValue, v => { if (v !== code.value) code.value = v })
watch(code, v => emit('update:modelValue', v))

const monoLang = () => {
  switch (props.language) {
    case 'cpp': return 'cpp'
    case 'java': return 'java'
    default: return 'python'
  }
}
</script>

<template>
  <VueMonacoEditor
    v-model:value="code"
    :language="monoLang()"
    :height="height"
    theme="vs-dark"
    :options="{
      automaticLayout: true,
      fontSize: 14,
      minimap: { enabled: false },
      scrollBeyondLastLine: false,
      tabSize: 2,
      wordWrap: 'on'
    }"
  />
</template>
