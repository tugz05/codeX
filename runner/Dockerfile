FROM node:20-bookworm

# Toolchains for running code + native builds for node-pty
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 \
    build-essential \
    default-jdk \
    ca-certificates \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /srv/runner

# Copy manifest(s) and install
COPY package.json package-lock.json* ./
# Use install so we don't require a perfectly in-sync lockfile in the image
RUN npm install --no-audit --no-fund

# App code
COPY server.js ./

EXPOSE 8088
CMD ["node", "server.js"]
