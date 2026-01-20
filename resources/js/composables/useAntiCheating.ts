import { ref, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'

interface ActivityLog {
  attemptableId: number
  attemptableType: string
  activityType: string
  description?: string
  metadata?: Record<string, any>
}

export function useAntiCheating(attemptableId: number, attemptableType: 'QuizAttempt' | 'ExamAttempt') {
  const violationCount = ref(0)
  const isMultipleTabOpen = ref(false)
  const tabSwitchCount = ref(0)
  const focusBlurCount = ref(0)
  const devToolsDetected = ref(false)
  
  const answerChangeHistory = ref<Map<number, { timestamp: number; from: any; to: any }>>(new Map())
  const questionTimeTracking = ref<Map<number, { startTime: number; timeSpent: number }>>(new Map())
  const currentQuestionId = ref<number | null>(null)

  // Multiple tab detection using BroadcastChannel
  let broadcastChannel: BroadcastChannel | null = null
  let tabId: string
  let cleanupMultipleTab: (() => void) | null = null

  // DevTools detection
  let devToolsCheckInterval: number | null = null

  const logActivity = async (activityType: string, description?: string, metadata?: Record<string, any>) => {
    try {
      const response = await fetch(route('student.attempt-activities.log'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          attemptable_id: attemptableId,
          attemptable_type: `App\\Models\\${attemptableType}`,
          activity_type: activityType,
          description,
          metadata,
        }),
      })

      if (!response.ok) {
        console.error('Failed to log activity:', await response.text())
      }
    } catch (error) {
      console.error('Error logging activity:', error)
    }
  }

  const showWarning = (message: string, type: 'warning' | 'error' = 'warning') => {
    if (type === 'error') {
      toast.error(message, { duration: 5000 })
    } else {
      toast.warning(message, { duration: 4000 })
    }
    violationCount.value++
  }

  // Tab/Window switching detection
  const handleVisibilityChange = () => {
    if (document.hidden) {
      tabSwitchCount.value++
      showWarning('Warning: You switched away from the exam window. This action has been logged.', 'warning')
      logActivity('tab_switch', 'User switched away from exam window', {
        count: tabSwitchCount.value,
        timestamp: Date.now(),
      })
    } else {
      logActivity('tab_return', 'User returned to exam window', {
        timestamp: Date.now(),
      })
    }
  }

  // Focus/Blur detection
  const handleFocus = () => {
    logActivity('focus', 'Window gained focus', { timestamp: Date.now() })
  }

  const handleBlur = () => {
    focusBlurCount.value++
    showWarning('Warning: The exam window lost focus. This action has been logged.', 'warning')
    logActivity('blur', 'Window lost focus', {
      count: focusBlurCount.value,
      timestamp: Date.now(),
    })
  }

  // Copy/Paste prevention
  const preventCopyPaste = (e: ClipboardEvent | KeyboardEvent) => {
    if (e instanceof ClipboardEvent) {
      e.preventDefault()
      showWarning('Copy and paste is disabled during the exam.', 'warning')
      logActivity('copy_paste_attempt', 'User attempted to copy/paste', {
        type: e.type,
        timestamp: Date.now(),
      })
      return false
    }

    // Block Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A
    if (e.ctrlKey || e.metaKey) {
      const key = e.key.toLowerCase()
      if (key === 'c' || key === 'v' || key === 'x' || key === 'a') {
        e.preventDefault()
        showWarning(`${key.toUpperCase()} shortcut is disabled during the exam.`, 'warning')
        logActivity('keyboard_shortcut_blocked', `Blocked ${key} shortcut`, {
          key,
          timestamp: Date.now(),
        })
        return false
      }
    }

    // Block Print Screen
    if (e.key === 'PrintScreen' || (e.ctrlKey && e.shiftKey && e.key === 'S')) {
      e.preventDefault()
      showWarning('Screenshots are not allowed during the exam.', 'warning')
      logActivity('screenshot_attempt', 'User attempted screenshot', {
        timestamp: Date.now(),
      })
      return false
    }

    // Block F12 and DevTools shortcuts
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C'))) {
      e.preventDefault()
      showWarning('Developer tools are not allowed during the exam.', 'error')
      devToolsDetected.value = true
      logActivity('devtools_shortcut_attempt', 'User attempted to open DevTools', {
        key: e.key,
        timestamp: Date.now(),
      })
      return false
    }
  }

  // Right-click prevention
  const preventContextMenu = (e: MouseEvent) => {
    e.preventDefault()
    showWarning('Right-click is disabled during the exam.', 'warning')
    logActivity('right_click_attempt', 'User attempted right-click', {
      timestamp: Date.now(),
    })
    return false
  }

  // DevTools detection
  const detectDevTools = () => {
    const widthThreshold = 160
    const heightThreshold = 160
    
    // Check if window size suggests DevTools is open
    if (
      window.outerHeight - window.innerHeight > heightThreshold ||
      window.outerWidth - window.innerWidth > widthThreshold
    ) {
      if (!devToolsDetected.value) {
        devToolsDetected.value = true
        showWarning('Developer tools detected. This action has been logged.', 'error')
        logActivity('devtools_detected', 'DevTools detected via window size', {
          outerWidth: window.outerWidth,
          outerHeight: window.outerHeight,
          innerWidth: window.innerWidth,
          innerHeight: window.innerHeight,
          timestamp: Date.now(),
        })
      }
    } else {
      devToolsDetected.value = false
    }

    // Check for console
    const checkConsole = () => {
      const element = new Image()
      Object.defineProperty(element, 'id', {
        get: () => {
          devToolsDetected.value = true
          showWarning('Developer tools detected. This action has been logged.', 'error')
          logActivity('devtools_detected', 'DevTools detected via console', {
            timestamp: Date.now(),
          })
        },
      })
      console.log(element)
      console.clear()
    }
    
    try {
      checkConsole()
    } catch (e) {
      // Ignore
    }
  }

  // Multiple tab detection
  const setupMultipleTabDetection = () => {
    tabId = `tab-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    broadcastChannel = new BroadcastChannel('exam-attempt-channel')

    // Listen for other tabs
    broadcastChannel.onmessage = (event) => {
      if (event.data.type === 'tab-opened' && event.data.tabId !== tabId) {
        isMultipleTabOpen.value = true
        showWarning('Multiple tabs detected. Please close other tabs immediately or the exam will be blocked.', 'error')
        logActivity('multiple_tab_detected', 'Multiple tabs detected', {
          otherTabId: event.data.tabId,
          timestamp: Date.now(),
        })
      }
    }

    // Announce this tab
    broadcastChannel.postMessage({
      type: 'tab-opened',
      tabId,
      attemptableId,
      timestamp: Date.now(),
    })

    // Periodic check
    const checkInterval = setInterval(() => {
      broadcastChannel?.postMessage({
        type: 'heartbeat',
        tabId,
        attemptableId,
        timestamp: Date.now(),
      })
    }, 2000)

    // Cleanup on unmount
    return () => {
      clearInterval(checkInterval)
      broadcastChannel?.postMessage({
        type: 'tab-closed',
        tabId,
        timestamp: Date.now(),
      })
      broadcastChannel?.close()
    }
  }

  // Answer change tracking
  const trackAnswerChange = (questionId: number, oldAnswer: any, newAnswer: any) => {
    const now = Date.now()
    
    if (!answerChangeHistory.value.has(questionId)) {
      answerChangeHistory.value.set(questionId, {
        timestamp: now,
        from: oldAnswer,
        to: newAnswer,
      })
    } else {
      const existing = answerChangeHistory.value.get(questionId)!
      // Track the change
      const changeCount = (existing as any).changeCount || 0
      ;(existing as any).changeCount = changeCount + 1
      ;(existing as any).to = newAnswer
      ;(existing as any).lastChangeTime = now
    }

    const changeCount = (answerChangeHistory.value.get(questionId) as any)?.changeCount || 0

    logActivity('answer_change', `Answer changed for question ${questionId}`, {
      question_id: questionId,
      from: oldAnswer,
      to: newAnswer,
      change_count: changeCount + 1,
      timestamp: now,
    })
  }

  // Time per question tracking
  const startQuestionTimer = (questionId: number) => {
    if (currentQuestionId.value && currentQuestionId.value !== questionId) {
      // Stop timer for previous question
      stopQuestionTimer(currentQuestionId.value)
    }

    currentQuestionId.value = questionId
    questionTimeTracking.value.set(questionId, {
      startTime: Date.now(),
      timeSpent: 0,
    })
  }

  const stopQuestionTimer = (questionId: number) => {
    const tracking = questionTimeTracking.value.get(questionId)
    if (tracking) {
      const timeSpent = Date.now() - tracking.startTime
      tracking.timeSpent = timeSpent

      logActivity('question_time', `Time spent on question ${questionId}`, {
        question_id: questionId,
        time_spent_ms: timeSpent,
        timestamp: Date.now(),
      })
    }
  }

  const getQuestionTime = (questionId: number): number => {
    const tracking = questionTimeTracking.value.get(questionId)
    return tracking ? tracking.timeSpent : 0
  }

  // Initialize
  onMounted(() => {
    // Tab/Window switching
    document.addEventListener('visibilitychange', handleVisibilityChange)
    
    // Focus/Blur
    window.addEventListener('focus', handleFocus)
    window.addEventListener('blur', handleBlur)
    
    // Copy/Paste prevention
    document.addEventListener('copy', preventCopyPaste)
    document.addEventListener('paste', preventCopyPaste)
    document.addEventListener('cut', preventCopyPaste)
    document.addEventListener('keydown', preventCopyPaste)
    
    // Right-click prevention
    document.addEventListener('contextmenu', preventContextMenu)
    
    // DevTools detection
    devToolsCheckInterval = window.setInterval(detectDevTools, 1000)
    
    // Multiple tab detection
    cleanupMultipleTab = setupMultipleTabDetection()

    // Log exam start
    logActivity('exam_started', 'Exam session started', {
      timestamp: Date.now(),
    })
  })

  onBeforeUnmount(() => {
    // Remove event listeners
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    window.removeEventListener('focus', handleFocus)
    window.removeEventListener('blur', handleBlur)
    document.removeEventListener('copy', preventCopyPaste)
    document.removeEventListener('paste', preventCopyPaste)
    document.removeEventListener('cut', preventCopyPaste)
    document.removeEventListener('keydown', preventCopyPaste)
    document.removeEventListener('contextmenu', preventContextMenu)
    
    // Clear intervals
    if (devToolsCheckInterval) {
      clearInterval(devToolsCheckInterval)
    }
    
    // Cleanup multiple tab detection
    if (cleanupMultipleTab) {
      cleanupMultipleTab()
    }

    // Stop all timers
    questionTimeTracking.value.forEach((_, questionId) => {
      stopQuestionTimer(questionId)
    })

    // Log exam end
    logActivity('exam_ended', 'Exam session ended', {
      violation_count: violationCount.value,
      tab_switch_count: tabSwitchCount.value,
      focus_blur_count: focusBlurCount.value,
      devtools_detected: devToolsDetected.value,
      multiple_tab_detected: isMultipleTabOpen.value,
      timestamp: Date.now(),
    })
  })

  return {
    violationCount,
    isMultipleTabOpen,
    tabSwitchCount,
    focusBlurCount,
    devToolsDetected,
    trackAnswerChange,
    startQuestionTimer,
    stopQuestionTimer,
    getQuestionTime,
    logActivity,
  }
}
