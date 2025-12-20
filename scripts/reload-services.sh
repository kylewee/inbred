#!/bin/bash

# Quick reload script - run this after making config changes
# Usage: ./reload-services.sh

echo "🔄 Reloading services after configuration changes..."

# Reload PHP-FPM (most important for environment variables)
echo "Reloading PHP-FPM..."
if sudo systemctl reload php8.3-fpm; then
    echo "✅ PHP-FPM reloaded successfully"
else
    echo "❌ PHP-FPM reload failed, restarting..."
    sudo systemctl restart php8.3-fpm
fi

# Reload Caddy (web server)
echo "Reloading Caddy..."
if sudo systemctl reload caddy; then
    echo "✅ Caddy reloaded successfully"
else
    echo "❌ Caddy reload failed, restarting..."
    sudo systemctl restart caddy
fi

# Check service status
echo ""
echo "📊 Service Status:"
systemctl is-active php8.3-fpm
systemctl is-active caddy

# Test system health
echo ""
echo "🏥 Testing system health..."
curl -s https://mechanicstaugustine.com/health.php | jq '.status' 2>/dev/null || echo "Health check failed"

echo "🎉 Reload complete!"