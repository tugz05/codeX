<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { onMounted, onBeforeUnmount } from 'vue'
import 'vue-sonner/style.css'

interface Props {
    title?: string
}

withDefaults(defineProps<Props>(), {
    title: 'Quiz/Exam',
})

// Prevent common distractions during exam
onMounted(() => {
    // Prevent right-click context menu
    const handleContextMenu = (e: MouseEvent) => {
        e.preventDefault()
    }
    
    // Prevent common keyboard shortcuts
    const handleKeyDown = (e: KeyboardEvent) => {
        // Allow F5 (refresh) but prevent Ctrl+R, Ctrl+Shift+R
        if ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R')) {
            e.preventDefault()
        }
        // Prevent F12 (dev tools)
        if (e.key === 'F12') {
            e.preventDefault()
        }
        // Prevent Ctrl+Shift+I (dev tools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'I') {
            e.preventDefault()
        }
    }
    
    // Prevent text selection (optional - can be removed if needed)
    document.addEventListener('contextmenu', handleContextMenu)
    document.addEventListener('keydown', handleKeyDown)
    
    // Store handlers for cleanup
    ;(window as any).__quizExamHandlers = { handleContextMenu, handleKeyDown }
})

onBeforeUnmount(() => {
    const handlers = (window as any).__quizExamHandlers
    if (handlers) {
        document.removeEventListener('contextmenu', handlers.handleContextMenu)
        document.removeEventListener('keydown', handlers.handleKeyDown)
        delete (window as any).__quizExamHandlers
    }
})
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-background">
        <slot />
        <Toaster />
    </div>
</template>
