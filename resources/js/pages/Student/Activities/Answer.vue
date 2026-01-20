<script setup lang="ts">
import AuthLayoutStudent from '@/layouts/AuthLayoutStudent.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { toast } from 'vue-sonner'
import { ArrowLeft, Save, Send, Play, FileCode2, TerminalSquare, Square } from 'lucide-vue-next'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import CodeEditor from '@/components/CodeEditor.vue'
import LiveTerminal from '@/components/LiveTerminal.vue'

const props = defineProps<{
  classlist: { id: string; name: string; room: string; academic_year: string }
  activity: { id: number; title: string; instruction: string | null; points: number | null; due_date: string | null; due_time: string | null }
  submission: { id:number; language:string|null; code:string|null; status:string; score:number|null; feedback:string|null; submitted_at:string|null } | null
  runnerUrl?: string
  runnerToken?: string
}>()

// Runner config (env or prop)
const RUNNER_WS_URL = (props.runnerUrl as string) || (import.meta.env.VITE_RUNNER_WS_URL || (window as any).VITE_RUNNER_WS_URL || 'ws://localhost:8088')
const RUNNER_TOKEN  = (props.runnerToken as string) || (import.meta.env.VITE_RUNNER_SHARED_TOKEN || (window as any).VITE_RUNNER_SHARED_TOKEN || 'change-me-please')

// Form state
const form = useForm({
  language: (props.submission?.language as any) ?? 'python', // 'python' | 'java' | 'cpp'
  code: props.submission?.code ?? '',
})

// Starter templates
const templates: Record<string, string> = {
  python:
`# Python 3 template
name = input("Your name: ")
print("Hello,", name)
`,
  java:
`import java.util.*;
public class Main {
  public static void main(String[] args) {
    Scanner sc = new Scanner(System.in);
    String s = sc.nextLine();
    System.out.println("Hello, " + s);
  }
}
`,
  cpp:
`#include <iostream>
#include <string>
int main() {
  std::string s;
  std::getline(std::cin, s);
  std::cout << "Hello, " << s << "\\n";
  return 0;
}
`,
}
if (!form.code) form.code = templates[form.language] ?? ''

// Terminal / run control
const termRef = ref<InstanceType<typeof LiveTerminal> | null>(null)
const runBusy = ref(false)

function runInteractive() {
  if (!termRef.value) return
  runBusy.value = true
  termRef.value.writeLine('\x1b[36m[launching program...]\x1b[0m')
  termRef.value.run(form.language as any, form.code)
}
function killRun() {
  termRef.value?.kill()
}
function onExited(p: {code: number|null, time_ms?: number}) {
  runBusy.value = false
  const t = typeof p.time_ms === 'number' ? ` in ${p.time_ms} ms` : ''
  if (p.code === 0) toast.success('Program finished' + t)
  else toast.error(`Program exited with code ${p.code}` + t)
}

// Save/Submit
function saveDraft() {
  form.post(route('student.activities.answer.save', [props.classlist.id, props.activity.id]), {
    preserveScroll: true,
    onSuccess: () => toast.success('Draft saved.'),
    onError: () => toast.error('Failed to save draft.'),
  })
}
function submitSolution() {
  form.post(route('student.activities.answer.submit', [props.classlist.id, props.activity.id]), {
    preserveScroll: true,
    onSuccess: async () => {
      toast.success('Submitted! Grading…')
      // Trigger AI grading (server will save evaluation)
      await fetch(route('student.activities.grade', [props.classlist.id, props.activity.id]), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content || '' }
      }).catch(()=>{})
      // Optionally: emit event so the evaluation component refreshes; or just rely on its periodic polling/onMounted
    },
    onError: () => toast.error('Submission failed.'),
  })
}


// Keyboard shortcut: Ctrl/Cmd + Enter to Run
function onKey(e: KeyboardEvent) {
  const isMac = navigator.platform.toLowerCase().includes('mac')
  const invoke = (isMac && e.metaKey && e.key === 'Enter') || (!isMac && e.ctrlKey && e.key === 'Enter')
  if (invoke) {
    e.preventDefault()
    if (!runBusy.value) runInteractive()
  }
}
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <Head :title="`Coding · ${props.activity.title}`" />

  <AuthLayoutStudent>
    <div class="min-h-[100dvh] bg-gradient-to-b from-white to-gray-50 dark:from-background dark:to-background/80">
      <!-- Top app bar -->
      <header class="sticky top-0 z-30 w-full border-b bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/60 shadow-sm">
        <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between px-4 py-3 md:py-4">
          <!-- Left: back + meta -->
          <div class="flex min-w-0 items-center gap-3">
            <Link :href="route('student.activities.show', [props.classlist.id, props.activity.id])" as="button">
              <Button type="button" variant="outline" size="sm" class="transition-colors hover:bg-accent">
                <ArrowLeft class="mr-1 h-4 w-4" /> Back
              </Button>
            </Link>
            <div class="min-w-0">
              <div class="truncate text-base font-semibold md:text-lg">{{ props.activity.title }}</div>
              <div class="truncate text-xs text-muted-foreground md:text-sm">
                {{ props.classlist.name }} • AY {{ props.classlist.academic_year }} • Room {{ props.classlist.room }}
              </div>
            </div>
          </div>

          <!-- Right: language + actions (no Run here to avoid duplication) -->
          <div class="flex shrink-0 items-center gap-2">
            <div class="hidden items-center gap-2 sm:flex">
              <div class="inline-flex items-center gap-2">
                <FileCode2 class="h-4 w-4 text-muted-foreground" />
                <Label class="text-xs md:text-sm">Language</Label>
              </div>
              <Select v-model="form.language" @update:modelValue="form.code = templates[form.language] ?? form.code">
                <SelectTrigger class="w-40 md:w-48">
                  <SelectValue placeholder="Language" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="python">Python</SelectItem>
                  <SelectItem value="java">Java</SelectItem>
                  <SelectItem value="cpp">C++ (G++)</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Separator class="hidden h-6 sm:block" orientation="vertical" />

            <Button type="button" variant="outline" size="sm" class="transition-colors hover:bg-accent" @click="saveDraft">
              <Save class="mr-1.5 h-4 w-4" /> Save
            </Button>
            <Button type="button" size="sm" class="transition-colors hover:bg-primary/90 text-white bg-primary" @click="submitSolution">
              <Send class="mr-1.5 h-4 w-4" /> Submit
            </Button>
          </div>
        </div>
      </header>

      <!-- Main two-pane workspace -->
      <main class="mx-auto w-full max-w-[1600px] px-2 py-2 md:px-4 md:py-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.1fr_0.9fr]">
          <!-- Editor Pane -->
          <section
            class="rounded-2xl border bg-card shadow-lg h-[calc(100dvh-140px)] md:h-[calc(100dvh-160px)] flex flex-col"
          >
            <!-- Small toolbar (shows on mobile too) -->
            <div class="flex items-center justify-between gap-2 border-b px-3 py-2 bg-muted/40">
              <div class="flex items-center gap-2 sm:hidden">
                <FileCode2 class="h-4 w-4 text-muted-foreground" />
                <Label class="text-xs">Language</Label>
                <Select v-model="form.language" @update:modelValue="form.code = templates[form.language] ?? form.code">
                  <SelectTrigger class="w-36">
                    <SelectValue placeholder="Language" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="python">Python</SelectItem>
                    <SelectItem value="java">Java</SelectItem>
                    <SelectItem value="cpp">C++ (G++)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="text-xs text-muted-foreground hidden md:block">
                Tip: <kbd class="rounded border px-1.5 py-0.5 text-[11px]">Ctrl</kbd> + <kbd class="rounded border px-1.5 py-0.5 text-[11px]">Enter</kbd> to Run
                <span class="hidden lg:inline"> • Auto-templates apply when you switch language</span>
              </div>
              <div />
            </div>
            <div class="flex-1 min-h-0">
              <CodeEditor
                v-model="form.code"
                :language="(form.language as any)"
                height="100%"
              />
            </div>
          </section>

          <!-- Console Pane -->
          <aside
            class="relative rounded-2xl border bg-card shadow-lg h-[calc(100dvh-140px)] md:h-[calc(100dvh-160px)] flex flex-col"
          >
            <!-- Console header: single place for Run/Stop -->
            <div class="flex items-center justify-between gap-2 border-b px-3 py-2 bg-muted/40">
              <div class="flex items-center gap-2">
                <TerminalSquare class="h-4 w-4" />
                <div class="text-sm font-medium">Live Console</div>
              </div>
              <div class="hidden md:flex items-center gap-2">
                <Button
                  type="button"
                  variant="secondary"
                  size="sm"
                  :disabled="runBusy"
                  @click="runInteractive"
                  title="Run (Ctrl/Cmd + Enter)"
                  class="transition-colors hover:bg-primary/90"
                >
                  <Play class="mr-1 h-4 w-4" /> {{ runBusy ? 'Running…' : 'Run' }}
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  :disabled="!runBusy"
                  @click="killRun"
                  title="Stop"
                  class="transition-colors hover:bg-destructive/80"
                >
                  <Square class="h-4 w-4" />
                </Button>
              </div>
            </div>
            <!-- Terminal fills remaining height -->
            <div class="flex-1 min-h-0 p-2">
              <div class="h-full w-full rounded-lg border overflow-auto">
                <LiveTerminal
                  ref="termRef"
                  :ws-url="RUNNER_WS_URL"
                  :token="RUNNER_TOKEN"
                  :auto-connect="true"
                  @exited="onExited"
                  @error="(m:string)=>toast.error(m)"
                />
              </div>
            </div>
            <!-- Mobile sticky Run/Stop bar -->
            <div class="md:hidden sticky bottom-0 left-0 w-full z-20 bg-card/95 border-t flex items-center justify-end gap-2 px-3 py-2 shadow-lg">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                :disabled="runBusy"
                @click="runInteractive"
                title="Run (Ctrl/Cmd + Enter)"
                class="transition-colors hover:bg-primary/90"
                style="min-width: 90px;"
              >
                <Play class="mr-1 h-4 w-4" /> {{ runBusy ? 'Running…' : 'Run' }}
              </Button>
              <Button
                type="button"
                variant="outline"
                size="icon"
                :disabled="!runBusy"
                @click="killRun"
                title="Stop"
                class="transition-colors hover:bg-destructive/80"
              >
                <Square class="h-4 w-4" />
              </Button>
            </div>
          </aside>
        </div>
      </main>
    </div>
  </AuthLayoutStudent>
</template>
