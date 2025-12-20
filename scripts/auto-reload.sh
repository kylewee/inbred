#!/bin/bash

# Auto-reload script for PHP-FPM and Caddy when config files change
# This prevents the "broken after terminal close" issue

DIRECTORIES_TO_WATCH="/home/kylewee/code/idk/projects/mechanicstaugustine.com/api /home/kylewee/code/idk/projects/mechanicstaugustine.com/crm /home/kylewee/code/idk/projects/mechanicstaugustine.com/voice"
SERVICES_TO_RELOAD="php8.3-fpm caddy"
LOG_FILE="/var/log/auto-reload.log"

echo "Starting auto-reload monitor..."
echo "Watching: $DIRECTORIES_TO_WATCH"
echo "Services to reload: $SERVICES_TO_RELOAD"
echo "Log file: $LOG_FILE"

# Create log file if it doesn't exist
sudo touch "$LOG_FILE"
sudo chmod 666 "$LOG_FILE"

log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | sudo tee -a "$LOG_FILE"
}

reload_services() {
    local reason="$1"
    log_message "Change detected: $reason"
    
    for service in $SERVICES_TO_RELOAD; do
        log_message "Reloading $service..."
        if sudo systemctl reload "$service"; then
            log_message "Successfully reloaded $service"
        else
            log_message "Failed to reload $service, trying restart..."
            sudo systemctl restart "$service"
        fi
    done
    
    log_message "Services reloaded successfully"
    echo "✅ Services reloaded due to: $reason"
}

# Function to check if inotify-tools is installed
check_dependencies() {
    if ! command -v inotifywait &> /dev/null; then
        echo "Installing inotify-tools..."
        sudo apt-get update && sudo apt-get install -y inotify-tools
    fi
}

# Main monitoring loop
monitor_changes() {
    log_message "Starting file monitoring..."
    
    while true; do
        # Monitor for changes in config files
        inotifywait -r -e modify,create,delete,move \
            --include '\.(php|env|conf|json)$' \
            $DIRECTORIES_TO_WATCH 2>/dev/null
        
        if [ $? -eq 0 ]; then
            reload_services "Configuration file changed"
            # Wait a bit to avoid multiple rapid reloads
            sleep 2
        fi
    done
}

# Check dependencies and start monitoring
check_dependencies
monitor_changes