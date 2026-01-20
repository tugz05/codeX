<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, nextTick } from 'vue'
import { Terminal } from 'xterm'
import { FitAddon } from 'xterm-addon-fit'
import { WebLinksAddon } from 'xterm-addon-web-links'
import 'xterm/css/xterm.css'

const props = defineProps<{
  wsUrl: string
  token?: string
  autoConnect?: boolean
}>()

const emit = defineEmits<{
  (e:'connected'):void
  (e:'disconnected'):void
  (e:'status', v:string):void
  (e:'exited', payload:{ code:number|null, time_ms?:number }):void
  (e:'error', msg:string):void
}>()

const termEl = ref<HTMLDivElement|null>(null)
let term: Terminal | null = null
let fit: FitAddon | null = null
let ws: WebSocket | null = null
let connectionPromise: Promise<void> | null = null

function connect(): Promise<void> {
  // If already connected, return resolved promise
  if (ws && ws.readyState === WebSocket.OPEN) {
    return Promise.resolve()
  }
  
  // If connecting, return the existing promise
  if (ws && ws.readyState === WebSocket.CONNECTING && connectionPromise) {
    return connectionPromise
  }

  // Clean up old connection if it exists
  if (ws) {
    try {
      ws.onopen = null
      ws.onclose = null
      ws.onerror = null
      ws.onmessage = null
      if (ws.readyState !== WebSocket.CLOSED) {
        ws.close()
      }
    } catch (e) {
      // Ignore cleanup errors
    }
    ws = null
  }

  // Create new connection promise
  connectionPromise = new Promise((resolve, reject) => {
    try {
      ws = new WebSocket(props.wsUrl)
      
      const timeout = setTimeout(() => {
        if (ws && ws.readyState !== WebSocket.OPEN) {
          ws.close()
          reject(new Error('WebSocket connection timeout'))
        }
      }, 10000) // 10 second timeout

      ws.onopen = () => {
        clearTimeout(timeout)
        emit('connected')
        writeLine('\x1b[32m[connected]\x1b[0m')
        ws?.send(JSON.stringify({ type:'hello', token: props.token }))
        resolve()
      }
      
      ws.onclose = (event) => {
        clearTimeout(timeout)
        connectionPromise = null
        emit('disconnected')
        writeLine('\x1b[31m[disconnected]\x1b[0m')
        if (event.code !== 1000) { // Not a normal closure
          writeLine(`\x1b[31m[close code: ${event.code}]\x1b[0m`)
        }
      }
      
      ws.onerror = (event) => {
        clearTimeout(timeout)
        connectionPromise = null
        const errorMsg = 'WebSocket connection error'
        emit('error', errorMsg)
        writeLine(`\r\n\x1b[31m[error] ${errorMsg}\x1b[0m\r\n`)
        reject(new Error(errorMsg))
      }

      ws.onmessage = (ev) => {
        try {
          const msg = JSON.parse(ev.data)
          switch (msg.type) {
            case 'hello': break
            case 'status':
              emit('status', msg.status)
              break
            case 'stdout':
              term?.write(msg.data)
              break
            case 'stderr':
              term?.write(`\r\n\x1b[31m${msg.data}\x1b[0m\r\n`)
              break
            case 'exit':
              emit('exited', { code: msg.code ?? null, time_ms: msg.time_ms })
              writeLine(`\x1b[33m[process exited: ${msg.code ?? 'null'} in ${msg.time_ms ?? 0} ms]\x1b[0m`)
              break
            case 'error':
              emit('error', msg.error || 'runner error')
              writeLine(`\r\n\x1b[31m[error] ${msg.error}\x1b[0m\r\n`)
              break
          }
        } catch {
          emit('error', 'Invalid message')
        }
      }
    } catch (error) {
      connectionPromise = null
      const errorMsg = error instanceof Error ? error.message : 'Failed to create WebSocket connection'
      emit('error', errorMsg)
      reject(new Error(errorMsg))
    }
  })

  return connectionPromise
}

function writeLine(s:string) {
  term?.writeln(s)
}

async function run(language: 'python'|'java'|'cpp', code: string) {
  try {
    // Ensure connection is established before sending
    if (!ws || ws.readyState !== WebSocket.OPEN) {
      await connect()
    }
    
    // Double-check connection state before sending
    if (ws && ws.readyState === WebSocket.OPEN) {
      nextTick(() => {
        fit?.fit()
        ws?.send(JSON.stringify({ type:'run', language, code, cols: term?.cols, rows: term?.rows }))
      })
    } else {
      emit('error', 'WebSocket is not connected')
      writeLine('\r\n\x1b[31m[error] WebSocket is not connected\r\n\x1b[0m')
    }
  } catch (error) {
    const errorMsg = error instanceof Error ? error.message : 'Failed to connect'
    emit('error', errorMsg)
    writeLine(`\r\n\x1b[31m[error] ${errorMsg}\r\n\x1b[0m`)
  }
}

function kill() {
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ type:'kill' }))
  } else {
    writeLine('\r\n\x1b[33m[warning] WebSocket is not connected\r\n\x1b[0m')
  }
}

onMounted(() => {
  term = new Terminal({
    fontSize: 14,
    convertEol: true,
    cursorBlink: true,
    theme: { background: '#0b0e14' }
  })
  fit = new FitAddon()
  term.loadAddon(fit)
  term.loadAddon(new WebLinksAddon())

  term.open(termEl.value!)
  fit.fit()

  // send keystrokes to runner
  term.onData((data) => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type:'stdin', data }))
    }
  })

  // handle resize
  const resize = () => {
    fit?.fit()
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type:'resize', cols: term?.cols, rows: term?.rows }))
    }
  }
  window.addEventListener('resize', resize)

  if (props.autoConnect) {
    connect().catch((error) => {
      const errorMsg = error instanceof Error ? error.message : 'Failed to auto-connect'
      emit('error', errorMsg)
    })
  }

  onBeforeUnmount(() => {
    window.removeEventListener('resize', resize)
    try { ws?.close() } catch {}
    term?.dispose()
  })
})

defineExpose({ connect, run, kill, writeLine })
</script>

<template>
  <div ref="termEl" class="h-full w-full rounded-lg border" />
</template>

<style scoped>
/* Ensure terminal fills parent */
:host, div { height: 100%; width: 100%; }
</style>
