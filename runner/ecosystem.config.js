// PM2 ecosystem configuration for code runner
// Install PM2: npm install -g pm2
// Start: pm2 start ecosystem.config.js

module.exports = {
  apps: [{
    name: 'code-runner',
    script: './server.js',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 8088,
      RUNNER_SHARED_TOKEN: process.env.RUNNER_SHARED_TOKEN || 'change-me-please'
    },
    error_file: './logs/error.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    max_restarts: 10,
    min_uptime: '10s',
    max_memory_restart: '500M',
    watch: false
  }]
};
