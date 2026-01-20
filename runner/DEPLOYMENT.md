# Code Runner Deployment Guide

This guide explains how to deploy the code runner service to various hosting environments, including Hostinger.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Hostinger Deployment Options](#hostinger-deployment-options)
3. [VPS/Cloud Deployment](#vpscloud-deployment)
4. [Docker Deployment](#docker-deployment)
5. [Configuration](#configuration)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

The code runner requires:
- **Node.js** (v18 or higher)
- **Python 3** (for Python code execution)
- **G++** (for C++ compilation)
- **JDK** (Java Development Kit for Java compilation and execution)
- **npm** (for installing dependencies)

## Hostinger Deployment Options

Hostinger shared hosting typically **does not support**:
- Direct shell access
- Installing system packages (compilers, runtimes)
- Running Node.js services
- Docker containers

### Option 1: Separate VPS for Runner (Recommended)

Since Hostinger shared hosting cannot run the code runner, you need a separate VPS or cloud server:

1. **Get a VPS** (from Hostinger VPS, DigitalOcean, AWS, etc.)
2. **Deploy the runner** on the VPS (see [VPS/Cloud Deployment](#vpscloud-deployment))
3. **Configure your Laravel app** to connect to the runner via WebSocket

**Recommended VPS Providers:**
- Hostinger VPS (if available)
- DigitalOcean Droplets ($5-10/month)
- AWS EC2 (pay-as-you-go)
- Linode
- Vultr

### Option 2: Use Hostinger VPS (If Available)

If you have Hostinger VPS access:

1. SSH into your VPS
2. Follow the [VPS/Cloud Deployment](#vpscloud-deployment) guide
3. Configure firewall to allow port 8088 (or your chosen port)

### Option 3: Cloud Functions/Serverless (Advanced)

For serverless deployment:
- AWS Lambda (with container support)
- Google Cloud Functions
- Azure Functions

*Note: This requires significant code modifications.*

## VPS/Cloud Deployment

### Step 1: Prepare the Server

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install Node.js (v18+)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version
npm --version
```

### Step 2: Install Dependencies

```bash
# Run the setup script
cd runner
chmod +x setup.sh
./setup.sh
```

Or install manually:

```bash
# Install Python, C++, Java
sudo apt-get install -y python3 build-essential default-jdk

# Verify installations
python3 --version
g++ --version
javac -version
java -version
```

### Step 3: Install Node.js Dependencies

```bash
cd runner
npm install
```

### Step 4: Configure Environment

Create a `.env` file or set environment variables:

```bash
export PORT=8088
export RUNNER_SHARED_TOKEN=your-secure-random-token-here
```

**Generate a secure token:**
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

### Step 5: Start the Server

#### Option A: Using PM2 (Recommended for Production)

```bash
# Install PM2 globally
sudo npm install -g pm2

# Start with PM2
cd runner
pm2 start ecosystem.config.js

# Save PM2 configuration
pm2 save

# Setup PM2 to start on boot
pm2 startup
# Follow the instructions shown
```

#### Option B: Using systemd

Create `/etc/systemd/system/code-runner.service`:

```ini
[Unit]
Description=Code Runner WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/runner
Environment="PORT=8088"
Environment="RUNNER_SHARED_TOKEN=your-token-here"
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl daemon-reload
sudo systemctl enable code-runner
sudo systemctl start code-runner
sudo systemctl status code-runner
```

#### Option C: Direct Node.js (Development Only)

```bash
node server.js
```

### Step 6: Configure Firewall

```bash
# Allow WebSocket port (default 8088)
sudo ufw allow 8088/tcp
sudo ufw reload
```

### Step 7: Configure Nginx Reverse Proxy (Optional but Recommended)

If you want to use a domain/subdomain:

```nginx
# /etc/nginx/sites-available/code-runner
map $http_upgrade $connection_upgrade {
    default upgrade;
    '' close;
}

server {
    listen 80;
    server_name runner.yourdomain.com;

    location / {
        proxy_pass http://localhost:8088;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/code-runner /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Docker Deployment

If you have Docker support:

```bash
cd runner
docker-compose up -d
```

Or build manually:

```bash
docker build -t code-runner .
docker run -d \
  -p 8088:8088 \
  -e RUNNER_SHARED_TOKEN=your-token-here \
  --name code-runner \
  --restart unless-stopped \
  code-runner
```

## Configuration

### Laravel Application Configuration

In your Laravel `.env` file:

```env
# WebSocket URL (use wss:// for HTTPS, ws:// for HTTP)
VITE_RUNNER_WS_URL=ws://your-runner-server-ip:8088
# Or with domain:
VITE_RUNNER_WS_URL=wss://runner.yourdomain.com

# Shared token (must match runner's RUNNER_SHARED_TOKEN)
VITE_RUNNER_SHARED_TOKEN=your-secure-random-token-here
```

### Security Considerations

1. **Use HTTPS/WSS in production** - Configure SSL certificate for WebSocket
2. **Use strong tokens** - Generate random tokens, don't use defaults
3. **Firewall rules** - Only allow connections from your Laravel server IP
4. **Rate limiting** - Consider implementing rate limiting
5. **Resource limits** - Set memory and CPU limits for code execution

## Troubleshooting

### Check if services are running

```bash
# Check Node.js process
ps aux | grep node

# Check PM2 status
pm2 status

# Check systemd service
sudo systemctl status code-runner

# Check logs
pm2 logs code-runner
# or
sudo journalctl -u code-runner -f
```

### Verify dependencies

```bash
# Check Python
python3 --version

# Check G++
g++ --version

# Check Java
javac -version
java -version
```

### Test WebSocket connection

```bash
# Install wscat
npm install -g wscat

# Test connection
wscat -c ws://localhost:8088
# Then send: {"type":"hello","token":"your-token"}
```

### Common Issues

1. **Port already in use**: Change PORT in environment or kill existing process
2. **Permission denied**: Check file permissions and user running the service
3. **Compiler not found**: Run setup script or install dependencies manually
4. **Connection refused**: Check firewall and ensure service is running

## Monitoring

### PM2 Monitoring

```bash
pm2 monit
```

### Logs

```bash
# PM2 logs
pm2 logs code-runner

# Systemd logs
sudo journalctl -u code-runner -f
```

## Updates

To update the runner:

```bash
cd runner
git pull  # if using git
npm install
pm2 restart code-runner
```

## Support

For issues:
1. Check logs first
2. Verify all dependencies are installed
3. Test WebSocket connection manually
4. Check firewall and network configuration
