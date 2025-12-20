#!/bin/bash
#
# Startup script for mechanicstaugustine.com
# Starts all required services in the correct order
#
# Usage: sudo ./scripts/startup-services.sh

set -e  # Exit on error

echo "=========================================="
echo "Starting mechanicstaugustine.com services"
echo "=========================================="
echo ""

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if service is running
check_service() {
    local service=$1
    if systemctl is-active --quiet "$service"; then
        echo -e "${GREEN}✓${NC} $service is running"
        return 0
    else
        echo -e "${RED}✗${NC} $service failed to start"
        return 1
    fi
}

# Function to start service with retry
start_service() {
    local service=$1
    local wait_time=${2:-3}

    echo -n "Starting $service... "

    if systemctl start "$service" 2>/dev/null; then
        sleep "$wait_time"
        if check_service "$service"; then
            return 0
        fi
    fi

    echo -e "${RED}Failed${NC}"
    return 1
}

echo "Step 1: Starting database layer..."
echo "-----------------------------------"

# Start MySQL (required for CRM)
if ! start_service mysql 5; then
    echo -e "${RED}ERROR: MySQL failed to start. Voice system will not work.${NC}"
    exit 1
fi

# Start PostgreSQL (optional, for Go backend)
if systemctl list-unit-files | grep -q "^postgresql.service"; then
    start_service postgresql 3 || echo -e "${YELLOW}Warning: PostgreSQL not started (optional service)${NC}"
fi

echo ""
echo "Step 2: Starting application layer..."
echo "--------------------------------------"

# Start PHP-FPM (required for voice webhooks)
if ! start_service php8.3-fpm 3; then
    echo -e "${RED}ERROR: PHP-FPM failed to start. Voice system will not work.${NC}"
    exit 1
fi

echo ""
echo "Step 3: Starting web server..."
echo "-------------------------------"

# Start Caddy (required for web traffic)
if ! start_service caddy 3; then
    echo -e "${RED}ERROR: Caddy failed to start. Website will not be accessible.${NC}"
    exit 1
fi

echo ""
echo "Step 4: Verifying system health..."
echo "-----------------------------------"

# Wait for services to stabilize
sleep 2

# Check all critical services
all_ok=true

echo "Critical services status:"
check_service mysql || all_ok=false
check_service php8.3-fpm || all_ok=false
check_service caddy || all_ok=false

# Optional services
echo ""
echo "Optional services status:"
check_service postgresql 2>/dev/null || echo -e "${YELLOW}○${NC} postgresql not running (optional)"

echo ""
echo "=========================================="

if [ "$all_ok" = true ]; then
    echo -e "${GREEN}SUCCESS: All critical services are running!${NC}"
    echo ""
    echo "Testing website health..."

    # Test local health endpoint
    if curl -s -f http://localhost/health.php > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Website is responding locally"
    else
        echo -e "${YELLOW}Warning: Health check failed (website may still be starting)${NC}"
    fi

    echo ""
    echo "Voice system is ready to receive calls at: +19042175152"
    echo "Website accessible at: https://mechanicstaugustine.com"
else
    echo -e "${RED}ERROR: Some critical services failed to start${NC}"
    echo "Check logs with: sudo journalctl -xe"
    exit 1
fi

echo "=========================================="
