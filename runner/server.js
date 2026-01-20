import { WebSocketServer, WebSocket } from 'ws';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import { writeFile, mkdtemp, rm } from 'fs/promises';
import os from 'os';
import path from 'path';
import { spawn } from 'node-pty';
import { exec as execSync } from 'child_process';
import { promisify } from 'util';
import { v4 as uuidv4 } from 'uuid';

const execAsync = promisify(execSync);

const PORT = process.env.PORT || 8088;
const SHARED_TOKEN = process.env.RUNNER_SHARED_TOKEN || ''; // set in .env
const TMP_PREFIX = 'live-run-';

// Detect available commands
const availableCommands = {
  python: null,
  gpp: null,
  javac: null,
  java: null,
  bash: null,
  sh: null
};

// Check command availability
async function checkCommand(cmd, alternatives = []) {
  const allCommands = [cmd, ...alternatives];
  for (const command of allCommands) {
    try {
      await execAsync(`which ${command} 2>/dev/null || command -v ${command} 2>/dev/null`);
      return command;
    } catch {
      continue;
    }
  }
  return null;
}

// Initialize command detection
async function detectCommands() {
  availableCommands.python = await checkCommand('python3', ['python', 'py']);
  availableCommands.gpp = await checkCommand('g++', ['gcc']);
  availableCommands.javac = await checkCommand('javac');
  availableCommands.java = await checkCommand('java');
  availableCommands.bash = await checkCommand('bash', ['sh']);
  
  console.log('[runner] Detected commands:', {
    python: availableCommands.python || 'NOT FOUND',
    gpp: availableCommands.gpp || 'NOT FOUND',
    javac: availableCommands.javac || 'NOT FOUND',
    java: availableCommands.java || 'NOT FOUND',
    shell: availableCommands.bash || 'NOT FOUND'
  });
}

// Initialize on startup
detectCommands().catch(err => {
  console.error('[runner] Error detecting commands:', err);
});

const wss = new WebSocketServer({ port: PORT }, () => {
  console.log(`[runner] WebSocket server listening on :${PORT}`);
  console.log(`[runner] Environment: ${process.platform} ${process.arch}`);
});

// Health check endpoint (if HTTP server is added later)
wss.on('listening', () => {
  console.log(`[runner] Server ready. Available languages:`);
  console.log(`  - Python: ${availableCommands.python ? '✓' : '✗'}`);
  console.log(`  - C++: ${availableCommands.gpp ? '✓' : '✗'}`);
  console.log(`  - Java: ${availableCommands.javac && availableCommands.java ? '✓' : '✗'}`);
});

wss.on('connection', (ws) => {
  let pty = null;
  let tempDir = null;
  let currentLang = null;
  let alive = true;

  const send = (msg) => {
    if (alive && ws.readyState === WebSocket.OPEN) {
      try {
        ws.send(JSON.stringify(msg));
      } catch (error) {
        console.error('[runner] Error sending message:', error);
      }
    }
  };

  ws.on('message', async (buf) => {
    let msg;
    try {
      msg = JSON.parse(buf.toString());
    } catch {
      send({ type: 'error', error: 'Invalid JSON' });
      return;
    }

    // Basic shared-secret check
    if (msg.type === 'hello') {
      if (SHARED_TOKEN && msg.token !== SHARED_TOKEN) {
        send({ type: 'error', error: 'Auth failed' });
        ws.close(1008, 'Auth failed');
        return;
      }
      send({ type: 'hello', ok: true });
      return;
    }

    // Safety: only allow after hello
    // (You can enforce a state machine if you want)

    if (msg.type === 'run') {
      // Kill previous
      if (pty) {
        try { pty.kill(); } catch {}
        pty = null;
      }
      if (tempDir) {
        try { await rm(tempDir, { recursive: true, force: true }); } catch {}
        tempDir = null;
      }

      const { language, code } = msg;
      if (!['python', 'java', 'cpp'].includes(language)) {
        send({ type: 'error', error: 'Unsupported language' });
        return;
      }

      try {
        tempDir = await mkdtemp(path.join(os.tmpdir(), TMP_PREFIX));
        currentLang = language;

        let cmd, args, cwd = tempDir;
        const shell = availableCommands.bash || 'sh';

        if (language === 'python') {
          if (!availableCommands.python) {
            send({ type: 'error', error: 'Python is not installed. Please install Python 3 to run Python code.' });
            return;
          }
          const file = path.join(tempDir, 'main.py');
          await writeFile(file, code, 'utf8');
          cmd = availableCommands.python;
          args = [file];
        }

        if (language === 'cpp') {
          if (!availableCommands.gpp) {
            send({ type: 'error', error: 'G++ compiler is not installed. Please install build-essential (Linux) or Xcode Command Line Tools (macOS) to compile C++ code.' });
            return;
          }
          const src = path.join(tempDir, 'main.cpp');
          const out = path.join(tempDir, 'a.out');
          await writeFile(src, code, 'utf8');

          // Compile first (blocking, captured separately)
          const compileCmd = shell === 'bash' 
            ? `g++ -O2 -std=c++17 "${src}" -o "${out}" 2>&1`
            : `g++ -O2 -std=c++17 ${src} -o ${out} 2>&1`;
          
          const compile = spawn(shell, shell === 'bash' ? ['-lc', compileCmd] : ['-c', compileCmd], {
            name: 'xterm-color',
            cols: 80, rows: 30, cwd: tempDir, env: process.env
          });
          let compileOutput = '';
          await new Promise((resolve) => {
            compile.onData(data => compileOutput += data);
            compile.onExit(() => resolve());
          });
          if (compileOutput.trim() !== '') {
            send({ type: 'stderr', data: compileOutput });
            send({ type: 'exit', code: 1 });
            return;
          }

          // Check if executable was created
          try {
            const { access } = await import('fs/promises');
            await access(out);
          } catch {
            send({ type: 'error', error: 'Compilation failed: executable not created' });
            return;
          }

          cmd = shell;
          args = shell === 'bash' ? ['-lc', `"${out}"`] : ['-c', out];
        }

        if (language === 'java') {
          if (!availableCommands.javac || !availableCommands.java) {
            send({ type: 'error', error: 'Java compiler (javac) or runtime (java) is not installed. Please install JDK to run Java code.' });
            return;
          }
          const src = path.join(tempDir, 'Main.java');
          await writeFile(src, code, 'utf8');

          const compileCmd = shell === 'bash'
            ? `javac "${src}" 2>&1`
            : `javac ${src} 2>&1`;

          const compile = spawn(shell, shell === 'bash' ? ['-lc', compileCmd] : ['-c', compileCmd], {
            name: 'xterm-color',
            cols: 80, rows: 30, cwd: tempDir, env: process.env
          });
          let compileOutput = '';
          await new Promise((resolve) => {
            compile.onData(data => compileOutput += data);
            compile.onExit(() => resolve());
          });
          if (compileOutput.trim() !== '') {
            send({ type: 'stderr', data: compileOutput });
            send({ type: 'exit', code: 1 });
            return;
          }

          // Check if class file was created
          try {
            const { access } = await import('fs/promises');
            await access(path.join(tempDir, 'Main.class'));
          } catch {
            send({ type: 'error', error: 'Compilation failed: class file not created' });
            return;
          }

          cmd = shell;
          args = shell === 'bash' ? ['-lc', 'java Main'] : ['-c', 'java Main'];
        }

        // Spawn the program in a PTY (interactive)
        pty = spawn(cmd, args, {
          name: 'xterm-color',
          cols: msg.cols || 100,
          rows: msg.rows || 30,
          cwd,
          env: process.env
        });

        send({ type: 'status', status: 'started' });

        let start = Date.now();

        pty.onData(data => send({ type: 'stdout', data }));
        pty.onExit(({ exitCode }) => {
          const ms = Date.now() - start;
          send({ type: 'exit', code: exitCode, time_ms: ms });
          // cleanup
          try { if (pty) pty.kill(); } catch {}
          pty = null;
          // Keep tempDir for a moment; will be removed on next run/close
        });

      } catch (e) {
        send({ type: 'error', error: e.message || 'Spawn failed' });
      }
      return;
    }

    if (msg.type === 'stdin') {
      if (pty) pty.write(msg.data);
      return;
    }

    if (msg.type === 'resize') {
      if (pty && msg.cols && msg.rows) {
        try { pty.resize(msg.cols, msg.rows); } catch {}
      }
      return;
    }

    if (msg.type === 'kill') {
      if (pty) {
        try { pty.kill(); } catch {}
        pty = null;
        send({ type: 'exit', code: null });
      }
      return;
    }
  });

  ws.on('error', (error) => {
    console.error('[runner] WebSocket error:', error);
    alive = false;
  });

  ws.on('close', async () => {
    alive = false;
    if (pty) { try { pty.kill(); } catch {} }
    if (tempDir) { try { await rm(tempDir, { recursive: true, force: true }); } catch {} }
  });
});
