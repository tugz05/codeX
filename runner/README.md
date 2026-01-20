# Code Runner WebSocket Server

A WebSocket server for executing Python, C++, and Java code in real-time with interactive terminal support.

## Features

- ✅ Real-time code execution via WebSocket
- ✅ Interactive terminal (stdin/stdout/stderr)
- ✅ Support for Python 3, C++ (G++), and Java
- ✅ Automatic dependency detection
- ✅ Secure token-based authentication
- ✅ Process management and cleanup

## Quick Start

### Prerequisites

- Node.js 18+
- Python 3
- G++ (for C++)
- JDK (for Java)

### Installation

1. **Install system dependencies** (Linux):
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

2. **Install Node.js dependencies**:
   ```bash
   npm install
   ```

3. **Configure environment**:
   ```bash
   export PORT=8088
   export RUNNER_SHARED_TOKEN=$(node -e "console.log(require('crypto').randomBytes(32).toString('hex'))")
   ```

4. **Start the server**:
   ```bash
   node server.js
   ```

### Production Deployment

Use PM2 for production:

```bash
npm install -g pm2
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed deployment instructions.

## Configuration

Environment variables:

- `PORT` - WebSocket server port (default: 8088)
- `RUNNER_SHARED_TOKEN` - Authentication token (required in production)

## Protocol

### Client → Server Messages

```json
// Hello/authentication
{"type": "hello", "token": "your-token"}

// Run code
{"type": "run", "language": "python|java|cpp", "code": "...", "cols": 100, "rows": 30}

// Send stdin
{"type": "stdin", "data": "user input\n"}

// Resize terminal
{"type": "resize", "cols": 120, "rows": 40}

// Kill process
{"type": "kill"}
```

### Server → Client Messages

```json
// Connection established
{"type": "hello", "ok": true}

// Status update
{"type": "status", "status": "started"}

// Standard output
{"type": "stdout", "data": "Hello, World!\n"}

// Standard error
{"type": "stderr", "data": "Error message\n"}

// Process exited
{"type": "exit", "code": 0, "time_ms": 1234}

// Error occurred
{"type": "error", "error": "Error message"}
```

## Supported Languages

- **Python 3**: Uses `python3` command
- **C++**: Compiles with `g++ -O2 -std=c++17`
- **Java**: Compiles with `javac` and runs with `java`

The server automatically detects available commands and provides helpful error messages if dependencies are missing.

## Security

- Token-based authentication
- Process isolation (temporary directories)
- Automatic cleanup of temporary files
- Resource limits (configure via PM2/systemd)

## Development

```bash
# Install dependencies
npm install

# Run in development
node server.js

# With auto-reload (using nodemon)
npm install -g nodemon
nodemon server.js
```

## License

See main project license.
