# Quick Start Guide

Get your 3-tier Home Affordability Application running in under 30 minutes!

---

## Prerequisites Checklist

- [ ] AWS account with EC2 access
- [ ] SSH key pair (.pem file downloaded)
- [ ] FRED API key from https://fred.stlouisfed.org/docs/api/api_key.html (free, takes 2 minutes)
- [ ] Terminal/SSH client

---

## 5-Minute Overview

This application helps homebuyers see which houses they can afford based on their income.

**User Flow:**
1. User enters zip code, annual income, bedrooms, bathrooms
2. System searches database, gets current mortgage rate
3. Calculates monthly payments (includes tax, insurance)
4. Shows which homes are affordable (≤28% of monthly income)

**Technical Flow:**
```
Internet → Server 1 (Web UI) 
        → Server 2 (PHP/Apache) 
        → Server 3 (MySQL) + FRED API
```

---

## Step 1: Launch EC2 Instances (10 minutes)

### Server 3 (Database) - Launch First

```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Storage: 20 GB
Security Group: Create new "sg-db-tier"
  - SSH (22) from your IP
  - MySQL (3306) from your IP (temporary)
Tag: Name = HomeFind-DB-Server3
```

**Note the Private IP:** _________________

### Server 2 (Application)

```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Storage: 20 GB
Security Group: Create new "sg-app-tier"
  - SSH (22) from your IP
  - HTTP (80) from your IP (temporary)
Tag: Name = HomeFind-App-Server2
```

**Note the Private IP:** _________________

### Server 1 (Web)

```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Storage: 10 GB
Security Group: Create new "sg-web-tier"
  - SSH (22) from your IP
  - HTTP (80) from 0.0.0.0/0
Elastic IP: Allocate and associate
Tag: Name = HomeFind-Web-Server1
```

**Note the Public IP:** _________________ ← This is your application URL!

---

## Step 2: Setup Server 3 - Database (5 minutes)

```bash
# SSH into Server 3
ssh -i your-key.pem ubuntu@SERVER3_PUBLIC_IP

# Update and install MySQL
sudo apt update && sudo apt install mysql-server -y

# Secure MySQL (set root password, answer Y to all prompts)
sudo mysql_secure_installation

# Configure for remote access
sudo sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
sudo systemctl restart mysql

# Upload database files (from your local machine)
exit  # Exit from server
scp -i your-key.pem server3/*.sql ubuntu@SERVER3_IP:~/

# SSH back in and setup database
ssh -i your-key.pem ubuntu@SERVER3_IP
sudo mysql -u root -p << EOF
source /home/ubuntu/schema.sql
source /home/ubuntu/sample_data.sql
ALTER USER 'appuser'@'%' IDENTIFIED BY 'YourSecurePassword123!';
FLUSH PRIVILEGES;
SELECT COUNT(*) FROM housing_db.listings;
EOF

# Should show 50 listings
# Note your password: _________________
```

**✅ Server 3 complete when:** `SELECT COUNT(*) FROM housing_db.listings;` returns 50

---

## Step 3: Setup Server 2 - Application (7 minutes)

```bash
# SSH into Server 2
ssh -i your-key.pem ubuntu@SERVER2_PUBLIC_IP

# Install Apache and PHP
sudo apt update && sudo apt install apache2 php libapache2-mod-php php-mysql php-curl php-json -y
sudo systemctl start apache2

# Upload application files (from your local machine)
exit  # Exit from server
cd server2
tar -czf server2-app.tar.gz api/ includes/ setup.sh test.php
scp -i your-key.pem server2-app.tar.gz ubuntu@SERVER2_IP:~/

# SSH back in and deploy
ssh -i your-key.pem ubuntu@SERVER2_IP
cd ~
tar -xzf server2-app.tar.gz
bash setup.sh

# Edit configuration files
nano includes/db.php
# Update line 12: define('DB_HOST', 'SERVER3_PRIVATE_IP');
# Update line 16: define('DB_PASS', 'YourSecurePassword123!');
# Save: Ctrl+X, Y, Enter

nano includes/fred.php
# Update line 11: define('FRED_API_KEY', 'your_fred_api_key');
# Save: Ctrl+X, Y, Enter

# Test configuration
php -r "require 'includes/db.php'; print_r(testDatabaseConnection());"
# Should show: success => true

php -r "require 'includes/fred.php'; print_r(testFredAPI());"
# Should show current mortgage rate

# Deploy to Apache
sudo cp -r api includes test.php /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Test in browser (temporary - use your IP)
# Visit: http://SERVER2_PUBLIC_IP/test.php
# All sections should show ✅
```

**✅ Server 2 complete when:** test.php shows all green checkmarks

---

## Step 4: Setup Server 1 - Web Interface (3 minutes)

```bash
# SSH into Server 1
ssh -i your-key.pem ubuntu@SERVER1_PUBLIC_IP

# Install Apache
sudo apt update && sudo apt install apache2 -y
sudo systemctl start apache2

# Upload web files (from your local machine)
exit  # Exit from server
cd server1
tar -czf server1-web.tar.gz *.html *.css *.js
scp -i your-key.pem server1-web.tar.gz ubuntu@SERVER1_IP:~/

# SSH back in and deploy
ssh -i your-key.pem ubuntu@SERVER1_IP
cd ~
tar -xzf server1-web.tar.gz
sudo rm /var/www/html/index.html  # Remove default page

# Edit script.js to point to Server 2
sed -i "s/YOUR_SERVER2_IP_HERE/SERVER2_PRIVATE_IP/" script.js
# Replace SERVER2_PRIVATE_IP with actual IP

sudo cp *.html *.css *.js /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Test in browser
# Visit: http://SERVER1_PUBLIC_IP
```

**✅ Server 1 complete when:** HomeFind page loads with search form

---

## Step 5: Test the Complete System (5 minutes)

### Test 1: Phoenix (Affordable)
```
Visit: http://SERVER1_PUBLIC_IP
Zip Code: 85001
Annual Income: 75000
Bedrooms: 2
Bathrooms: 2
Click "Search Homes"

Expected: See several homes marked as ✓ Affordable
```

### Test 2: Beverly Hills (Expensive)
```
Zip Code: 90210
Annual Income: 100000
Bedrooms: 3
Bathrooms: 2

Expected: Most homes marked as ✗ Not Affordable
```

### Test 3: JSON Endpoint
```
Visit: http://SERVER2_PUBLIC_IP/api/search-json.php?zip=78701&income=100000&beds=3&baths=2

Expected: See JSON response with listings
```

**✅ System complete when:** All three tests pass

---

## Step 6: Secure the Deployment (5 minutes)

### Update Security Groups

**Server 3 (sg-db-tier):**
```
Remove: MySQL (3306) from your IP
Add: MySQL (3306) from Server 2 private IP ONLY
```

**Server 2 (sg-app-tier):**
```
Remove: HTTP (80) from your IP
Add: HTTP (80) from Server 1 private IP ONLY
```

### Delete Test File

```bash
# On Server 2
ssh -i your-key.pem ubuntu@SERVER2_IP
sudo rm /var/www/html/test.php
```

### Test External Access

```
✅ Can access: http://SERVER1_PUBLIC_IP (from anywhere)
❌ Cannot access: http://SERVER2_IP (should timeout)
❌ Cannot access: Server 3 MySQL from internet
```

**✅ Security complete when:** Only Server 1 is publicly accessible

---

## You're Done! 🎉

**Your application is now live at:** `http://SERVER1_PUBLIC_IP`

### Share With Your Team

```
Application URL: http://_________________ (Server 1 Public IP)
JSON API Example: http://SERVER2_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2

GitHub Repository: https://github.com/ank5688/aws-app
```

---

## Quick Troubleshooting

**Problem:** Can't connect to database from Server 2
```bash
# From Server 2, test connection:
telnet SERVER3_PRIVATE_IP 3306
# If this fails, check Security Group on Server 3
```

**Problem:** FRED API not working
```bash
# Check if API key is valid:
curl "https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US&api_key=YOUR_KEY&file_type=json&limit=1"
# Should return JSON with rate data
```

**Problem:** Search returns no results
```bash
# On Server 3, verify data:
mysql -u appuser -p -h localhost housing_db -e "SELECT zipcode, COUNT(*) FROM listings GROUP BY zipcode;"
```

**Problem:** Page loads but search doesn't work
```bash
# Check browser console (F12)
# Check Server 2 logs:
sudo tail -f /var/log/apache2/error.log
```

---

## Next Steps

1. **Add SSL/HTTPS** - Use Let's Encrypt for free SSL certificate
2. **Add More Data** - Expand sample_data.sql with more listings
3. **Create Network Diagram** - Document your architecture
4. **Setup Monitoring** - Configure CloudWatch alerts
5. **Backup Database** - Setup automated backups to S3

---

## Resources

- **Full Documentation:** See `docs/SETUP.md`
- **API Reference:** See `docs/API_DOCUMENTATION.md`
- **Architecture:** See `docs/NETWORK_DIAGRAM.md`
- **Deployment Checklist:** See `docs/DEPLOYMENT_CHECKLIST.md`

---

## Support

**Having issues?** Check these in order:

1. Verify all IPs are correct (Server 2 IP in Server 1's script.js, Server 3 IP in Server 2's db.php)
2. Check Security Groups allow traffic between tiers
3. Verify credentials (database password, FRED API key)
4. Check logs: `sudo tail -f /var/log/apache2/error.log`
5. Review full setup guide: `docs/SETUP.md`

---

**Deployment Time:** ~30 minutes  
**Cost:** ~$35/month for 3 t2.micro instances  
**Difficulty:** Intermediate

Good luck with your deployment! 🚀
