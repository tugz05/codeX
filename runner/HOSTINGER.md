# Hostinger Deployment Guide

This is a **quick start guide** specifically for deploying the code runner on Hostinger.

## Important: Hostinger Shared Hosting Limitations

⚠️ **Hostinger shared hosting CANNOT run the code runner directly** because:
- No shell/SSH access
- No ability to install compilers (g++, javac)
- No Node.js service support
- No Docker support

## Solution: Use a Separate VPS

You need a **separate VPS** (Virtual Private Server) to run the code runner. Here are your options:

### Option 1: Hostinger VPS (Recommended if Available)

If Hostinger offers VPS hosting:

1. **Purchase Hostinger VPS** (minimum $5-10/month)
2. **Follow the VPS deployment steps below**

### Option 2: Alternative VPS Providers

Popular alternatives:
- **DigitalOcean** - $5/month (1GB RAM, 1 vCPU)
- **Vultr** - $5/month
- **Linode** - $5/month
- **AWS EC2** - Pay as you go (free tier available)

## VPS Deployment Steps

### Step 1: Connect to Your VPS

```bash
ssh root@your-vps-ip
```

### Step 2: Install Node.js

```bash
# Update system
apt-get update && apt-get upgrade -y

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Verify
node --version  # Should show v20.x.x
npm --version
```

### Step 3: Install Compilers and Runtimes

```bash
# Install Python, C++, Java
apt-get install -y python3 build-essential default-jdk

# Verify installations
python3 --version
g++ --version
javac -version
java -version
```

### Step 4: Upload Runner Code

**Option A: Using Git**
```bash
cd /opt
git clone your-repo-url runner
cd runner/runner
npm install
```

**Option B: Using SCP (from your local machine)**
```bash
# From your local machine
scp -r runner/ root@your-vps-ip:/opt/runner
ssh root@your-vps-ip
cd /opt/runner
npm install
```

**Option C: Using File Manager**
1. Upload `runner/` folder via Hostinger File Manager
2. SSH into VPS
3. Navigate to uploaded folder
4. Run `npm install`

### Step 5: Configure Environment

```bash
cd /opt/runner

# Generate a secure token
TOKEN=$(node -e "console.log(require('crypto').randomBytes(32).toString('hex'))")
echo "Generated token: $TOKEN"
# Save this token - you'll need it for Laravel .env

# Create .env file or set environment variables
export PORT=8088
export RUNNER_SHARED_TOKEN=$TOKEN
```

### Step 6: Install PM2 (Process Manager)

```bash
npm install -g pm2
```

### Step 7: Start the Runner

```bash
cd /opt/runner

# Update ecosystem.config.js with your token
# Edit ecosystem.config.js and set RUNNER_SHARED_TOKEN

# Start with PM2
pm2 start ecosystem.config.js

# Save PM2 configuration
pm2 save

# Setup PM2 to start on boot
pm2 startup
# Follow the instructions shown (usually: sudo env PATH=... pm2 startup systemd -u root --hp /root)
```

### Step 8: Configure Firewall

```bash
# Allow WebSocket port
ufw allow 8088/tcp
ufw reload

# Check status
ufw status
```

### Step 9: Test the Connection

From your local machine or Laravel server:

```bash
# Install wscat
npm install -g wscat

# Test connection
wscat -c ws://your-vps-ip:8088
# Send: {"type":"hello","token":"your-token"}
# Should receive: {"type":"hello","ok":true}
```

### Step 10: Configure Laravel Application

In your Laravel `.env` file (on Hostinger):

```env
# Use your VPS IP address
VITE_RUNNER_WS_URL=ws://your-vps-ip:8088

# Use the token you generated in Step 5
VITE_RUNNER_SHARED_TOKEN=your-generated-token-here
```

**For production with SSL**, use `wss://`:
```env
VITE_RUNNER_WS_URL=wss://runner.yourdomain.com
```

### Step 11: Setup Nginx Reverse Proxy (Optional but Recommended)

If you want to use a domain name instead of IP:

```bash
# Install Nginx
apt-get install -y nginx

# Create configuration
nano /etc/nginx/sites-available/code-runner
```

Add this configuration:

```nginx
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
    }
}
```

```bash
# Enable site
ln -s /etc/nginx/sites-available/code-runner /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx

# Setup SSL with Let's Encrypt
apt-get install -y certbot python3-certbot-nginx
certbot --nginx -d runner.yourdomain.com
```

## Quick Commands Reference

```bash
# Check runner status
pm2 status

# View logs
pm2 logs code-runner

# Restart runner
pm2 restart code-runner

# Stop runner
pm2 stop code-runner

# Check if port is listening
netstat -tulpn | grep 8088
# or
ss -tulpn | grep 8088
```

## Troubleshooting

### Runner not starting
```bash
# Check PM2 logs
pm2 logs code-runner --lines 50

# Check if dependencies are installed
python3 --version
g++ --version
javac -version
```

### Connection refused
```bash
# Check if service is running
pm2 status

# Check firewall
ufw status

# Check if port is open
telnet your-vps-ip 8088
```

### Compiler not found errors
```bash
# Reinstall dependencies
apt-get install -y python3 build-essential default-jdk
```

## Cost Estimate

- **VPS**: $5-10/month (DigitalOcean, Vultr, etc.)
- **Domain** (optional): $10-15/year
- **Total**: ~$5-10/month

## Security Checklist

- ✅ Use strong, random tokens
- ✅ Configure firewall (only allow port 8088)
- ✅ Use HTTPS/WSS in production
- ✅ Keep system updated: `apt-get update && apt-get upgrade`
- ✅ Monitor logs regularly: `pm2 logs code-runner`

## Support

If you encounter issues:
1. Check PM2 logs: `pm2 logs code-runner`
2. Verify all dependencies are installed
3. Test WebSocket connection manually
4. Check firewall rules
