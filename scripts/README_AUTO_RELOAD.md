# Auto-Reload Scripts

These scripts prevent the "broken after terminal close" issue by automatically reloading services when configuration changes.

## Quick Reload (Use this after making changes)

```bash
./scripts/reload-services.sh
```

This script:
- Reloads PHP-FPM (fixes environment variable issues)
- Reloads Caddy (web server)
- Shows service status
- Tests system health

## Auto-Monitor (Run in background)

```bash
./scripts/auto-reload.sh
```

This script:
- Monitors config files for changes
- Automatically reloads services when files change
- Logs all actions to `/var/log/auto-reload.log`
- Prevents caching issues

## When to Use

**After editing any of these files:**
- `api/.env.local.php`
- `crm/config/database.php`
- `voice/*.php` files
- Any `.env` or configuration files

**Just run:**
```bash
./scripts/reload-services.sh
```

## Install Dependencies

The auto-monitor script will automatically install `inotify-tools` if needed.

## Why This Fixes The Problem

- PHP-FPM caches environment variables on startup
- When you close terminal, environment variables disappear
- PHP-FPM keeps running with old cached config
- Reloading PHP-FPM forces it to read fresh environment variables
- This prevents the "it was working, now it's broken" issue