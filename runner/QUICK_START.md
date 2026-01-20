# Quick Start Guide

## For Hostinger Deployment

Since Hostinger shared hosting cannot run the code runner, you need a **separate VPS**. See [HOSTINGER.md](./HOSTINGER.md) for detailed instructions.

### TL;DR - 5 Steps

1. **Get a VPS** ($5/month from DigitalOcean/Vultr/etc.)
2. **SSH into VPS** and run:
   ```bash
   apt-get update
   apt-get install -y nodejs python3 build-essential default-jdk
   ```
3. **Upload runner code** to `/opt/runner`
4. **Install and start**:
   ```bash
   cd /opt/runner
   npm install
   export RUNNER_SHARED_TOKEN=$(node -e "console.log(require('crypto').randomBytes(32).toString('hex'))")
   npm install -g pm2
   pm2 start ecosystem.config.js
   pm2 save && pm2 startup
   ```
5. **Configure Laravel** `.env`:
   ```env
   VITE_RUNNER_WS_URL=ws://your-vps-ip:8088
   VITE_RUNNER_SHARED_TOKEN=your-token-from-step-4
   ```

That's it! Your code runner is now running.

## Local Development

```bash
# Install dependencies
npm install

# Run setup script (installs Python, C++, Java)
chmod +x setup.sh
./setup.sh

# Start server
npm start
```

## Verify Installation

```bash
# Check if all compilers are available
python3 --version
g++ --version
javac -version
java -version
```

## Test Connection

```bash
# Install wscat
npm install -g wscat

# Connect to runner
wscat -c ws://localhost:8088

# Send hello message
{"type":"hello","token":"your-token"}
```

## Production Commands

```bash
# Start with PM2
pm2 start ecosystem.config.js

# View logs
pm2 logs code-runner

# Restart
pm2 restart code-runner

# Stop
pm2 stop code-runner
```

For more details, see:
- [DEPLOYMENT.md](./DEPLOYMENT.md) - Full deployment guide
- [HOSTINGER.md](./HOSTINGER.md) - Hostinger-specific guide
- [README.md](./README.md) - General documentation
