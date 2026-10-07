# Configuration Template

Use this template to track your deployment configuration details.

---

## Server Information

### Server 1 (Web Tier)
```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Public IP: ________________
Private IP: 10.0.1.10
Elastic IP: ________________
Security Group: sg-web-tier
SSH Key: ________________.pem
Subnet: public-subnet (10.0.1.0/24)
```

### Server 2 (Application Tier)
```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Public IP: ________________ (for setup only)
Private IP: 10.0.2.10
Security Group: sg-app-tier
SSH Key: ________________.pem
Subnet: private-subnet (10.0.2.0/24)
```

### Server 3 (Database Tier)
```
Instance Type: t2.micro
AMI: Ubuntu 20.04 LTS
Public IP: ________________ (for setup only)
Private IP: 10.0.3.10
Security Group: sg-db-tier
SSH Key: ________________.pem
Subnet: private-subnet (10.0.3.0/24)
```

---

## Database Configuration

### MySQL Credentials
```
Host: 10.0.3.10 (Server 3 private IP)
Port: 3306
Database Name: housing_db
Table Name: listings

Root User:
  Username: root
  Password: ________________ (store securely!)

Application User:
  Username: appuser
  Password: ________________ (store securely!)
  Permissions: SELECT only on housing_db
```

---

## External API Configuration

### FRED (Federal Reserve Economic Data)
```
API Endpoint: https://api.stlouisfed.org/fred/series/observations
API Key: ________________ (get from https://fred.stlouisfed.org/docs/api/api_key.html)
Series ID: MORTGAGE30US
Cache Duration: 3600 seconds (1 hour)
Fallback Rate: 6.75%
```

---

## Security Groups

### sg-web-tier (Server 1)

**Inbound Rules:**
```
Rule 1:
  Type: HTTP
  Protocol: TCP
  Port: 80
  Source: 0.0.0.0/0
  Description: Allow HTTP from Internet

Rule 2:
  Type: HTTPS
  Protocol: TCP
  Port: 443
  Source: 0.0.0.0/0
  Description: Allow HTTPS from Internet (optional)

Rule 3:
  Type: SSH
  Protocol: TCP
  Port: 22
  Source: ________________ (your IP)
  Description: SSH access for admin
```

**Outbound Rules:**
```
Rule 1:
  Type: HTTP
  Protocol: TCP
  Port: 80
  Destination: 10.0.2.10/32 (Server 2)
  Description: HTTP to Server 2

Rule 2:
  Type: HTTPS
  Protocol: TCP
  Port: 443
  Destination: 0.0.0.0/0
  Description: HTTPS for system updates
```

### sg-app-tier (Server 2)

**Inbound Rules:**
```
Rule 1:
  Type: HTTP
  Protocol: TCP
  Port: 80
  Source: 10.0.1.10/32 (Server 1)
  Description: HTTP from Server 1

Rule 2:
  Type: SSH
  Protocol: TCP
  Port: 22
  Source: ________________ (your IP)
  Description: SSH access for admin
```

**Outbound Rules:**
```
Rule 1:
  Type: MySQL
  Protocol: TCP
  Port: 3306
  Destination: 10.0.3.10/32 (Server 3)
  Description: MySQL to Server 3

Rule 2:
  Type: HTTPS
  Protocol: TCP
  Port: 443
  Destination: 0.0.0.0/0
  Description: HTTPS for FRED API and updates
```

### sg-db-tier (Server 3)

**Inbound Rules:**
```
Rule 1:
  Type: MySQL
  Protocol: TCP
  Port: 3306
  Source: 10.0.2.10/32 (Server 2)
  Description: MySQL from Server 2

Rule 2:
  Type: MySQL
  Protocol: TCP
  Port: 3306
  Source: ________________ (your IP)
  Description: MySQL access for admin (temporary, remove after setup)

Rule 3:
  Type: SSH
  Protocol: TCP
  Port: 22
  Source: ________________ (your IP)
  Description: SSH access for admin
```

**Outbound Rules:**
```
Rule 1:
  Type: All Traffic
  Protocol: All
  Port: All
  Destination: 0.0.0.0/0
  Description: Allow responses
```

---

## File Locations

### Server 1
```
Document Root: /var/www/html/
Config Files:
  - /var/www/html/index.html
  - /var/www/html/styles.css
  - /var/www/html/script.js

Apache Config: /etc/apache2/apache2.conf
Apache Logs:
  - Access: /var/log/apache2/access.log
  - Error: /var/log/apache2/error.log
```

### Server 2
```
Document Root: /var/www/html/
Application Files:
  - /var/www/html/api/search.php
  - /var/www/html/api/search-json.php
  - /var/www/html/includes/db.php
  - /var/www/html/includes/mortgage.php
  - /var/www/html/includes/fred.php
  - /var/www/html/test.php (DELETE before production!)

Apache Config: /etc/apache2/apache2.conf
PHP Config: /etc/php/7.4/apache2/php.ini
Apache Logs:
  - Access: /var/log/apache2/access.log
  - Error: /var/log/apache2/error.log
Cache: /tmp/fred_rate_cache.json
```

### Server 3
```
MySQL Config: /etc/mysql/mysql.conf.d/mysqld.cnf
MySQL Data: /var/lib/mysql/
Database Setup Scripts:
  - ~/schema.sql
  - ~/sample_data.sql

MySQL Logs:
  - General: /var/log/mysql/mysql.log
  - Error: /var/log/mysql/error.log
  - Slow Query: /var/log/mysql/slow.log
```

---

## SSH Commands

### Connect to Servers
```bash
# Server 1 (Web Tier)
ssh -i /path/to/key.pem ubuntu@SERVER1_PUBLIC_IP

# Server 2 (Application Tier)
ssh -i /path/to/key.pem ubuntu@SERVER2_PUBLIC_IP

# Server 3 (Database Tier)
ssh -i /path/to/key.pem ubuntu@SERVER3_PUBLIC_IP
```

### Copy Files to Servers
```bash
# Server 1
scp -i /path/to/key.pem server1-web.tar.gz ubuntu@SERVER1_IP:~/

# Server 2
scp -i /path/to/key.pem server2-app.tar.gz ubuntu@SERVER2_IP:~/

# Server 3
scp -i /path/to/key.pem server3/schema.sql ubuntu@SERVER3_IP:~/
scp -i /path/to/key.pem server3/sample_data.sql ubuntu@SERVER3_IP:~/
```

---

## Configuration File Updates

### Server 2: db.php
```php
// Line 8-12: Update these values
define('DB_HOST', '10.0.3.10');  // Server 3 private IP
define('DB_PORT', '3306');
define('DB_NAME', 'housing_db');
define('DB_USER', 'appuser');
define('DB_PASS', 'YOUR_PASSWORD_HERE');  // Replace with actual password
```

### Server 2: fred.php
```php
// Line 9: Update this value
define('FRED_API_KEY', 'YOUR_FRED_API_KEY_HERE');  // Replace with actual API key
```

### Server 1: script.js
```javascript
// Line 5: Update this value
const SERVER2_URL = 'http://10.0.2.10';  // Server 2 private IP
```

---

## URLs and Endpoints

### User-Facing URL
```
Main Application: http://SERVER1_PUBLIC_IP
```

### Internal API Endpoints (Server 2)
```
HTML Endpoint:
http://10.0.2.10/api/search.php?zip=90210&income=100000&beds=3&baths=2

JSON Endpoint:
http://10.0.2.10/api/search-json.php?zip=90210&income=100000&beds=3&baths=2

Test Page (setup only):
http://10.0.2.10/test.php
```

### External API
```
FRED API:
https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US&api_key=YOUR_KEY&file_type=json&limit=1&sort_order=desc
```

---

## Calculation Parameters

```
Down Payment: 20% of purchase price
Loan Term: 30 years (360 months)
Property Tax: 1.2% of home value annually (0.1% monthly)
Home Insurance: $100 per month (fixed estimate)
Affordability Rule: Monthly payment ≤ 28% of gross monthly income

Monthly Payment Formula:
M = P[r(1+r)^n] / [(1+r)^n - 1]
Where:
  M = Monthly payment
  P = Loan amount (80% of price)
  r = Monthly interest rate (annual rate / 12)
  n = Number of payments (360)

Total Payment = Monthly P&I + Property Tax + Insurance
```

---

## Test Data

### Available Zip Codes
```
90210 - Beverly Hills, CA (Expensive: $1.95M-$4.2M)
94102 - San Francisco, CA (Expensive: $950K-$2.1M)
10001 - New York, NY (Expensive: $950K-$3.1M)
78701 - Austin, TX (Moderate: $475K-$1.05M)
98101 - Seattle, WA (Moderate: $650K-$1.85M)
33101 - Miami, FL (Moderate: $525K-$1.65M)
60601 - Chicago, IL (Moderate: $395K-$1.15M)
80202 - Denver, CO (Moderate: $425K-$1.25M)
02101 - Boston, MA (Expensive: $625K-$1.85M)
85001 - Phoenix, AZ (Affordable: $245K-$675K)
97201 - Portland, OR (Moderate: $395K-$1.13M)
```

### Test Search Queries
```
Affordable Search:
  Zip: 85001
  Income: 75000
  Beds: 2
  Baths: 2

Moderate Search:
  Zip: 78701
  Income: 100000
  Beds: 3
  Baths: 2

Expensive Search:
  Zip: 90210
  Income: 200000
  Beds: 4
  Baths: 3
```

---

## Backup and Recovery

### Database Backup Command
```bash
# On Server 3
mysqldump -u root -p housing_db > housing_db_backup_$(date +%Y%m%d).sql

# Download backup
scp -i key.pem ubuntu@SERVER3_IP:~/housing_db_backup_*.sql ./
```

### Database Restore Command
```bash
# On Server 3
mysql -u root -p housing_db < housing_db_backup_YYYYMMDD.sql
```

### AMI Snapshots
```
Server 1 AMI ID: ________________
Server 2 AMI ID: ________________
Server 3 AMI ID: ________________
```

---

## Team Information

```
Team Members:
  1. ________________ (Role: ________________)
  2. ________________ (Role: ________________)
  3. ________________ (Role: ________________)
  4. ________________ (Role: ________________)

Project Repository: ________________
Documentation Location: ________________
Deployment Date: ________________
Course/Section: ________________
Instructor: ________________
```

---

## Support Contacts

```
AWS Support: https://console.aws.amazon.com/support/
FRED API Support: https://fred.stlouisfed.org/docs/api/
MySQL Documentation: https://dev.mysql.com/doc/
PHP Documentation: https://www.php.net/docs.php
Apache Documentation: https://httpd.apache.org/docs/

Team Contact:
  Email: ________________
  Slack/Teams: ________________
```

---

## Notes and Observations

```
Installation Notes:
[Add any issues encountered during setup, solutions applied, or deviations from standard process]



Performance Notes:
[Record any performance observations, slow queries, or optimization opportunities]



Security Notes:
[Document any security concerns, additional hardening applied, or security incidents]



Feature Requests:
[Track ideas for additional features or improvements]



```

---

**Last Updated:** ________________  
**Updated By:** ________________
