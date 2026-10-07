# Setup and Deployment Guide

## Prerequisites

- 3 AWS EC2 instances (Amazon Linux 2 or Ubuntu 20.04+)
- AWS Security Groups configured
- SSH access to all servers
- FRED API key (free at https://fred.stlouisfed.org/docs/api/api_key.html)

## Architecture Overview

```
[Internet] 
    ↓
[Server 1 - Web Tier]  ← Public IP, Apache serving static files
    ↓ HTTP
[Server 2 - App Tier]  ← Apache + PHP, calls Server 3 and FRED API
    ↓ MySQL
[Server 3 - DB Tier]   ← MySQL, restricted access
```

---

## Server 3 Setup (Database Tier) - DO THIS FIRST

### 1. Launch EC2 Instance
- **Instance Type**: t2.micro or t3.micro
- **AMI**: Ubuntu 20.04 LTS
- **Storage**: 20 GB
- **Security Group**: 
  - Allow MySQL (3306) from Server 2's private IP only
  - Allow MySQL (3306) from your workstation IP (for setup/testing)
  - Allow SSH (22) from your IP

### 2. Install MySQL

```bash
# SSH into Server 3
ssh -i your-key.pem ubuntu@server3-public-ip

# Update system
sudo apt update
sudo apt upgrade -y

# Install MySQL
sudo apt install mysql-server -y

# Secure MySQL installation
sudo mysql_secure_installation
# Set root password
# Remove anonymous users: Y
# Disallow root login remotely: Y
# Remove test database: Y
# Reload privilege tables: Y
```

### 3. Configure MySQL for Remote Access

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Find the line:
#   bind-address = 127.0.0.1
# Change to:
#   bind-address = 0.0.0.0

# Save and exit (Ctrl+X, Y, Enter)

# Restart MySQL
sudo systemctl restart mysql
sudo systemctl enable mysql
```

### 4. Create Database and User

```bash
# Copy SQL files to server
# On your local machine:
scp -i your-key.pem server3/schema.sql ubuntu@server3-ip:~/
scp -i your-key.pem server3/sample_data.sql ubuntu@server3-ip:~/

# Back on Server 3:
# Login to MySQL as root
sudo mysql -u root -p

# Execute in MySQL prompt:
source /home/ubuntu/schema.sql
source /home/ubuntu/sample_data.sql

# Update the application user password
# IMPORTANT: Replace 'YourSecurePasswordHere' with a strong password
ALTER USER 'appuser'@'%' IDENTIFIED BY 'YourSecurePasswordHere';

# Grant permissions for Server 2's private IP
# Replace SERVER2_PRIVATE_IP with actual IP
GRANT SELECT ON housing_db.* TO 'appuser'@'SERVER2_PRIVATE_IP' IDENTIFIED BY 'YourSecurePasswordHere';
FLUSH PRIVILEGES;

# Verify database
USE housing_db;
SELECT COUNT(*) FROM listings;
# Should show 50 listings

# Exit MySQL
exit;
```

### 5. Test Database Connection

```bash
# From Server 3, test local connection
mysql -u appuser -p housing_db
# Enter password when prompted
# If successful, you'll see MySQL prompt

SELECT * FROM listings LIMIT 5;
exit;
```

**Note Server 3's Private IP**: You'll need this for Server 2 configuration.

---

## Server 2 Setup (Application Tier)

### 1. Launch EC2 Instance
- **Instance Type**: t2.micro or t3.micro
- **AMI**: Ubuntu 20.04 LTS
- **Storage**: 20 GB
- **Security Group**:
  - Allow HTTP (80) from Server 1's private IP only
  - Allow SSH (22) from your IP
  - Ensure outbound rules allow MySQL (3306) to Server 3
  - Ensure outbound rules allow HTTPS (443) for FRED API

### 2. Install Apache and PHP

```bash
# SSH into Server 2
ssh -i your-key.pem ubuntu@server2-public-ip

# Update system
sudo apt update
sudo apt upgrade -y

# Install Apache, PHP, and MySQL client
sudo apt install apache2 php libapache2-mod-php php-mysql php-curl php-json -y

# Enable and start Apache
sudo systemctl enable apache2
sudo systemctl start apache2
```

### 3. Deploy Application Files

```bash
# On your local machine, create deployment package
cd server2
tar -czf server2-app.tar.gz api/ includes/

# Copy to Server 2
scp -i your-key.pem server2-app.tar.gz ubuntu@server2-ip:~/

# Back on Server 2:
# Extract files to Apache directory
cd /var/www/html
sudo tar -xzf ~/server2-app.tar.gz
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### 4. Configure Database Connection

```bash
# Edit db.php with Server 3 details
sudo nano /var/www/html/includes/db.php

# Update these lines:
# define('DB_HOST', 'SERVER3_PRIVATE_IP');
# define('DB_PASS', 'YourSecurePasswordHere');

# Save and exit
```

### 5. Configure FRED API

```bash
# Get your free FRED API key from:
# https://fred.stlouisfed.org/docs/api/api_key.html

# Edit fred.php
sudo nano /var/www/html/includes/fred.php

# Update this line:
# define('FRED_API_KEY', 'your_actual_api_key_here');

# Save and exit

# Create cache directory
sudo mkdir -p /tmp
sudo chmod 777 /tmp
```

### 6. Test Server 2

```bash
# Test database connection
php -r "require '/var/www/html/includes/db.php'; print_r(testDatabaseConnection());"

# Should show: success => true

# Test FRED API
php -r "require '/var/www/html/includes/fred.php'; print_r(testFredAPI());"

# Should show current mortgage rate

# Test from browser (temporarily open port 80 to your IP)
# Visit: http://server2-public-ip/api/search.php?zip=90210&income=100000&beds=3&baths=2
```

**Note Server 2's Private IP**: You'll need this for Server 1 configuration.

---

## Server 1 Setup (Web/Presentation Tier)

### 1. Launch EC2 Instance
- **Instance Type**: t2.micro or t3.micro
- **AMI**: Ubuntu 20.04 LTS
- **Storage**: 10 GB
- **Security Group**:
  - Allow HTTP (80) from 0.0.0.0/0 (Internet)
  - Allow HTTPS (443) from 0.0.0.0/0 (optional, for SSL)
  - Allow SSH (22) from your IP

### 2. Install Apache

```bash
# SSH into Server 1
ssh -i your-key.pem ubuntu@server1-public-ip

# Update system
sudo apt update
sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y

# Enable and start Apache
sudo systemctl enable apache2
sudo systemctl start apache2
```

### 3. Deploy Web Files

```bash
# On your local machine
cd server1
tar -czf server1-web.tar.gz index.html styles.css script.js

# Copy to Server 1
scp -i your-key.pem server1-web.tar.gz ubuntu@server1-ip:~/

# Back on Server 1:
# Remove default Apache page
sudo rm /var/www/html/index.html

# Extract web files
cd /var/www/html
sudo tar -xzf ~/server1-web.tar.gz
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### 4. Configure Server 2 Connection

```bash
# Edit script.js with Server 2 IP
sudo nano /var/www/html/script.js

# Update this line:
# const SERVER2_URL = 'http://SERVER2_PRIVATE_IP';

# Save and exit
```

### 5. Test Server 1

```bash
# Visit in browser:
# http://server1-public-ip

# You should see the HomeFind search form
# Try a search with:
# Zip: 90210
# Income: 100000
# Beds: 3
# Baths: 2
```

---

## Security Hardening

### Server 3 (Database) Security

```bash
# On Server 3, verify firewall rules
sudo ufw allow from SERVER2_PRIVATE_IP to any port 3306
sudo ufw allow from YOUR_WORKSTATION_IP to any port 3306
sudo ufw allow 22
sudo ufw enable
sudo ufw status

# Verify MySQL users
sudo mysql -u root -p
SELECT user, host FROM mysql.user;
# Should only show appuser with specific IPs, not '%'
```

### Server 2 (Application) Security

```bash
# On Server 2
sudo ufw allow from SERVER1_PRIVATE_IP to any port 80
sudo ufw allow 22
sudo ufw allow out 3306  # To Server 3
sudo ufw allow out 443   # For FRED API
sudo ufw enable
```

### Server 1 (Web) Security

```bash
# On Server 1
sudo ufw allow 80
sudo ufw allow 443  # If using HTTPS
sudo ufw allow 22
sudo ufw enable
```

---

## Testing the Complete System

### 1. Test Database (Server 3)
```bash
mysql -u appuser -p -h SERVER3_PRIVATE_IP housing_db
SELECT COUNT(*) FROM listings;
exit;
```

### 2. Test API (Server 2)
```bash
curl "http://SERVER2_PRIVATE_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2"
# Should return JSON with listings
```

### 3. Test Web Interface (Server 1)
- Visit: `http://SERVER1_PUBLIC_IP`
- Fill in the form:
  - Zip: 90210
  - Income: 100000
  - Beds: 3
  - Baths: 2
- Click "Search Homes"
- You should see results with affordability calculations

### 4. Test JSON Endpoint
- Visit: `http://SERVER2_PRIVATE_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2`
- Should see formatted JSON response

---

## Troubleshooting

### Can't connect to database from Server 2

1. Check Server 3 MySQL is listening:
   ```bash
   sudo netstat -tlnp | grep 3306
   ```

2. Test network connectivity:
   ```bash
   # From Server 2:
   telnet SERVER3_PRIVATE_IP 3306
   ```

3. Check MySQL user permissions:
   ```bash
   sudo mysql -u root -p
   SELECT user, host FROM mysql.user WHERE user='appuser';
   ```

4. Check Security Group rules in AWS Console

### FRED API not working

1. Verify API key:
   ```bash
   curl "https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US&api_key=YOUR_KEY&file_type=json&limit=1&sort_order=desc"
   ```

2. Check PHP curl extension:
   ```bash
   php -m | grep curl
   ```

3. Check cache permissions:
   ```bash
   ls -la /tmp/fred_rate_cache.json
   ```

### Server 1 can't reach Server 2

1. Test from Server 1:
   ```bash
   curl "http://SERVER2_PRIVATE_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2"
   ```

2. Check Apache error logs:
   ```bash
   # On Server 2:
   sudo tail -f /var/log/apache2/error.log
   ```

3. Verify Security Group allows Server 1 → Server 2 on port 80

---

## Maintenance

### Backup Database
```bash
# On Server 3
mysqldump -u root -p housing_db > housing_db_backup_$(date +%Y%m%d).sql
```

### Update Mortgage Rate Cache
```bash
# On Server 2, clear cache to force fresh API call
sudo rm /tmp/fred_rate_cache.json
```

### View Logs
```bash
# Apache access logs
sudo tail -f /var/log/apache2/access.log

# Apache error logs
sudo tail -f /var/log/apache2/error.log

# PHP errors (if configured)
sudo tail -f /var/log/php/error.log
```

---

## Next Steps

1. **Add SSL/HTTPS** to Server 1 using Let's Encrypt
2. **Implement load balancing** for high availability
3. **Add monitoring** with CloudWatch
4. **Create AMI snapshots** for disaster recovery
5. **Implement additional features** from requirements

---

## Support

For issues or questions, refer to:
- MySQL documentation: https://dev.mysql.com/doc/
- PHP documentation: https://www.php.net/docs.php
- FRED API documentation: https://fred.stlouisfed.org/docs/api/
- Apache documentation: https://httpd.apache.org/docs/
