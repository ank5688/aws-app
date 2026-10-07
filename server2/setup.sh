#!/bin/bash
# Server 2 Setup Script
# Helps configure the application tier

echo "=================================="
echo "Server 2 Setup - Application Tier"
echo "=================================="
echo ""

# Check if running on Server 2
if [ ! -d "includes" ] || [ ! -d "api" ]; then
    echo "Error: This script must be run from the server2 directory"
    exit 1
fi

# Create configuration files from templates
echo "Step 1: Creating configuration files from templates..."

if [ ! -f "includes/db.php" ]; then
    if [ -f "includes/db.php.template" ]; then
        cp includes/db.php.template includes/db.php
        echo "✓ Created includes/db.php from template"
    else
        echo "✗ Template file includes/db.php.template not found"
        exit 1
    fi
else
    echo "! includes/db.php already exists (skipping)"
fi

if [ ! -f "includes/fred.php" ]; then
    if [ -f "includes/fred.php.template" ]; then
        cp includes/fred.php.template includes/fred.php
        echo "✓ Created includes/fred.php from template"
    else
        echo "✗ Template file includes/fred.php.template not found"
        exit 1
    fi
else
    echo "! includes/fred.php already exists (skipping)"
fi

echo ""
echo "Step 2: Configuration required"
echo "--------------------------------"
echo ""
echo "You must now edit the configuration files with your settings:"
echo ""
echo "1. Edit includes/db.php:"
echo "   - Update DB_HOST with Server 3's private IP"
echo "   - Update DB_PASS with your database password"
echo ""
echo "   Command: nano includes/db.php"
echo ""
echo "2. Edit includes/fred.php:"
echo "   - Update FRED_API_KEY with your API key"
echo "   - Get key from: https://fred.stlouisfed.org/docs/api/api_key.html"
echo ""
echo "   Command: nano includes/fred.php"
echo ""
echo "Step 3: Test configuration"
echo "-------------------------"
echo ""
echo "After editing, test your configuration:"
echo ""
echo "Test database connection:"
echo "  php -r \"require 'includes/db.php'; print_r(testDatabaseConnection());\""
echo ""
echo "Test FRED API:"
echo "  php -r \"require 'includes/fred.php'; print_r(testFredAPI());\""
echo ""
echo "Test via web interface:"
echo "  Visit: http://YOUR_SERVER2_IP/test.php"
echo ""
echo "Step 4: Deploy to Apache"
echo "------------------------"
echo ""
echo "Copy files to Apache document root:"
echo "  sudo cp -r api includes test.php /var/www/html/"
echo "  sudo chown -R www-data:www-data /var/www/html"
echo "  sudo chmod -R 755 /var/www/html"
echo ""
echo "Step 5: Security"
echo "----------------"
echo ""
echo "Before production deployment:"
echo "  sudo rm /var/www/html/test.php"
echo ""
echo "=================================="
echo "Setup complete! Follow the steps above to configure your server."
echo "=================================="
