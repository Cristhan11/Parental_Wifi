#!/bin/bash

################################################################################
# Raspberry Pi Setup Diagnostic Script
# 
# This script gathers information about your Raspberry Pi setup to help
# customize the documentation with your exact configuration.
# 
# Usage:
#   chmod +x scripts/check-raspberry-pi-setup.sh
#   ./scripts/check-raspberry-pi-setup.sh
# 
# Run this and share the output to update documentation with your exact setup.
################################################################################

echo "=========================================="
echo "Raspberry Pi Setup Diagnostic"
echo "=========================================="
echo ""

# Get current user
CURRENT_USER=$(whoami)
echo "Current User: $CURRENT_USER"
echo ""

# Get project directory
PROJECT_DIR=$(pwd)
echo "Project Directory: $PROJECT_DIR"
echo ""

# Check PHP version
echo "=== PHP Version ==="
PHP_VERSION=$(php -v | head -1 | awk '{print $2}' | cut -d'.' -f1,2)
echo "PHP Version: $(php -v | head -1)"
echo "PHP Major.Minor: $PHP_VERSION"
echo ""

# Check PHP-FPM service
echo "=== PHP-FPM Service ==="
PHP_FPM_SERVICE=""
for version in php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm php-fpm; do
    if systemctl list-unit-files | grep -q "^${version}"; then
        PHP_FPM_SERVICE="$version"
        echo "Found PHP-FPM service: $version"
        systemctl is-active $version > /dev/null 2>&1 && echo "  Status: active (running)" || echo "  Status: inactive"
        break
    fi
done

if [ -z "$PHP_FPM_SERVICE" ]; then
    echo "No PHP-FPM service found. Checking processes..."
    ps aux | grep php-fpm | grep -v grep | head -1
fi
echo ""

# Check Nginx
echo "=== Nginx ==="
if command -v nginx &> /dev/null; then
    echo "Nginx installed: Yes"
    echo "Nginx version: $(nginx -v 2>&1)"
    systemctl is-active nginx > /dev/null 2>&1 && echo "Status: active (running)" || echo "Status: inactive"
else
    echo "Nginx installed: No"
fi
echo ""

# Check MariaDB/MySQL
echo "=== Database Server ==="
if systemctl list-unit-files | grep -q "^mariadb"; then
    echo "Database: MariaDB"
    systemctl is-active mariadb > /dev/null 2>&1 && echo "Status: active (running)" || echo "Status: inactive"
elif systemctl list-unit-files | grep -q "^mysql"; then
    echo "Database: MySQL"
    systemctl is-active mysql > /dev/null 2>&1 && echo "Status: active (running)" || echo "Status: inactive"
else
    echo "Database: Not found"
fi
echo ""

# Check Nginx PHP-FPM socket configuration
echo "=== Nginx PHP-FPM Configuration ==="
NGINX_CONFIG="/etc/nginx/sites-available/parental_wifi"
if [ -f "$NGINX_CONFIG" ]; then
    echo "Nginx config file: $NGINX_CONFIG"
    FASTCGI_LINE=$(grep "fastcgi_pass" "$NGINX_CONFIG" | head -1)
    if [ -n "$FASTCGI_LINE" ]; then
        echo "PHP-FPM socket: $FASTCGI_LINE"
        # Extract socket path
        SOCKET_PATH=$(echo "$FASTCGI_LINE" | grep -oP 'unix:\K[^;]+')
        echo "Socket path: $SOCKET_PATH"
        if [ -n "$SOCKET_PATH" ] && [ -e "$SOCKET_PATH" ]; then
            echo "Socket exists: Yes"
        else
            echo "Socket exists: No"
        fi
    else
        echo "No fastcgi_pass found in config"
    fi
else
    echo "Nginx config file not found: $NGINX_CONFIG"
fi
echo ""

# Check PHP-FPM socket location
echo "=== PHP-FPM Socket Locations ==="
if [ -d "/var/run/php" ]; then
    echo "Sockets in /var/run/php/:"
    ls -la /var/run/php/ 2>/dev/null | grep "\.sock"
fi
if [ -d "/run/php" ]; then
    echo "Sockets in /run/php/:"
    ls -la /run/php/ 2>/dev/null | grep "\.sock"
fi
echo ""

# Check Git repository
echo "=== Git Repository ==="
if [ -d ".git" ]; then
    echo "Git repository: Yes"
    echo "Remote URL: $(git remote get-url origin 2>/dev/null || echo 'Not configured')"
    echo "Current branch: $(git branch --show-current 2>/dev/null || echo 'Unknown')"
else
    echo "Git repository: No"
fi
echo ""

# Summary
echo "=========================================="
echo "Summary for Documentation"
echo "=========================================="
echo "Username: $CURRENT_USER"
echo "Project Directory: $PROJECT_DIR"
echo "PHP Version: $PHP_VERSION"
echo "PHP-FPM Service: ${PHP_FPM_SERVICE:-Not found}"
echo "Nginx: $(command -v nginx &> /dev/null && echo 'Installed' || echo 'Not installed')"
echo "Database: $(systemctl list-unit-files | grep -E '^(mariadb|mysql)' | head -1 | awk '{print $1}' || echo 'Not found')"
echo "=========================================="

