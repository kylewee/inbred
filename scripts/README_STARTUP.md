# Service Startup Scripts

This directory contains scripts to start all required services for mechanicstaugustine.com in the correct order.

## Quick Start

### Manual Startup (After Reboot)

```bash
sudo ./scripts/startup-services.sh
```

This will start all services in the correct order:
1. MySQL (CRM database)
2. PHP-FPM (voice webhooks)
3. Caddy (web server)
4. PostgreSQL (optional, backend API)

## Automatic Startup on Boot

To make services start automatically when your computer reboots:

### Option 1: Enable Individual Services (Recommended)

```bash
# Enable services to start on boot
sudo systemctl enable mysql
sudo systemctl enable php8.3-fpm
sudo systemctl enable caddy
sudo systemctl enable postgresql  # Optional
```

This is the **recommended approach** because systemd handles dependencies automatically.

### Option 2: Custom Startup Service

If you need custom startup logic, use the provided systemd service:

```bash
# Copy service file to systemd directory
sudo cp scripts/mechanicstaugustine-startup.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Enable the startup service
sudo systemctl enable mechanicstaugustine-startup.service

# Test it
sudo systemctl start mechanicstaugustine-startup.service
sudo systemctl status mechanicstaugustine-startup.service
```

## Service Check Commands

### Check if all services are running

```bash
sudo systemctl status mysql php8.3-fpm caddy
```

### Restart all services

```bash
sudo systemctl restart mysql
sudo systemctl restart php8.3-fpm
sudo systemctl restart caddy
```

### View service logs

```bash
# MySQL logs
sudo journalctl -u mysql -f

# PHP-FPM logs
sudo journalctl -u php8.3-fpm -f
sudo tail -f /var/log/php8.3-fpm.log

# Caddy logs
sudo journalctl -u caddy -f

# Voice system logs
tail -f /home/kylewee/code/inbred/voice/voice.log
```

## Troubleshooting

### Service won't start

```bash
# Check detailed error
sudo systemctl status service-name
sudo journalctl -xe

# Try manual start with verbose output
sudo systemctl start service-name
```

### After configuration changes

```bash
# Reload PHP-FPM after .env.local.php changes
sudo systemctl reload php8.3-fpm

# Reload Caddy after Caddyfile changes
sudo systemctl reload caddy

# Restart MySQL after config changes
sudo systemctl restart mysql
```

### Test website functionality

```bash
# Local health check
curl http://localhost/health.php

# External access
curl https://mechanicstaugustine.com/health.php

# Test voice webhook
curl -X POST https://mechanicstaugustine.com/voice/incoming.php
```

## Service Startup Order

**Critical order for voice system:**

1. **MySQL** → CRM database must be available
2. **PHP-FPM** → Processes voice webhooks and CRM calls
3. **Caddy** → Routes incoming traffic to PHP

**Optional services:**

- **PostgreSQL** → Only needed if using Go backend API
- **Docker** → Only if running backend via docker-compose

## Files in this Directory

- `startup-services.sh` - Main startup script
- `mechanicstaugustine-startup.service` - Systemd service file
- `README_STARTUP.md` - This file
- `reload-services.sh` - Quick reload script
- `auto-reload.sh` - Development auto-reload watcher

## Related Documentation

- `/home/kylewee/code/inbred/CLAUDE.md` - Complete development guide
- `/home/kylewee/code/inbred/SERVICE_STATUS.md` - Service status tracking
- `/home/kylewee/code/inbred/DEPLOYMENT.md` - Production deployment guide
