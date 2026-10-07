# Deployment Checklist

Use this checklist to ensure proper deployment of the 3-tier Home Affordability Application.

---

## Pre-Deployment

### AWS Account Setup
- [ ] AWS account created and verified
- [ ] IAM user created with appropriate permissions
- [ ] AWS CLI configured with credentials
- [ ] SSH key pair created and downloaded (.pem file)

### External Services
- [ ] FRED API key obtained from https://fred.stlouisfed.org/docs/api/api_key.html
- [ ] API key tested with sample request

### Documentation Review
- [ ] Read README.md completely
- [ ] Review SETUP.md for deployment steps
- [ ] Review NETWORK_DIAGRAM.md for architecture understanding
- [ ] Review API_DOCUMENTATION.md for endpoint details

---

## Server 3 (Database Tier) - Deploy First

### EC2 Instance
- [ ] Instance launched (t2.micro, Ubuntu 20.04)
- [ ] Private IP address noted: `________________`
- [ ] Public IP address noted (for setup): `________________`
- [ ] Security group created (sg-db-tier)
- [ ] SSH key pair assigned

### Security Group Configuration
- [ ] Allow SSH (22) from admin IP
- [ ] Allow MySQL (3306) from Server 2 private IP (configure after Server 2 created)
- [ ] Allow MySQL (3306) from admin IP (temporary, for setup)
- [ ] Deny all other inbound traffic

### MySQL Installation
- [ ] System updated (`apt update && apt upgrade`)
- [ ] MySQL installed (`apt install mysql-server`)
- [ ] MySQL secured (`mysql_secure_installation`)
- [ ] Root password set and recorded securely
- [ ] MySQL configured for remote access (bind-address = 0.0.0.0)
- [ ] MySQL restarted successfully

### Database Setup
- [ ] schema.sql uploaded to server
- [ ] sample_data.sql uploaded to server
- [ ] schema.sql executed successfully
- [ ] sample_data.sql executed successfully
- [ ] Database `housing_db` created
- [ ] Table `listings` created with 50 records
- [ ] Application user `appuser` created
- [ ] User password changed from default
- [ ] User password recorded securely: `________________`
- [ ] User permissions granted (SELECT only)
- [ ] Connection tested from server: `mysql -u appuser -p housing_db`

### Verification
- [ ] Query test: `SELECT COUNT(*) FROM listings;` returns 50
- [ ] Query test: `SELECT * FROM listings WHERE zipcode='90210';` returns results
- [ ] Indexes verified: `SHOW INDEX FROM listings;`

---

## Server 2 (Application Tier) - Deploy Second

### EC2 Instance
- [ ] Instance launched (t2.micro, Ubuntu 20.04)
- [ ] Private IP address noted: `________________`
- [ ] Public IP address noted (for setup): `________________`
- [ ] Security group created (sg-app-tier)
- [ ] SSH key pair assigned
- [ ] Instance in private subnet

### Security Group Configuration
- [ ] Allow SSH (22) from admin IP
- [ ] Allow HTTP (80) from Server 1 private IP (configure after Server 1 created)
- [ ] Allow outbound MySQL (3306) to Server 3 private IP
- [ ] Allow outbound HTTPS (443) for FRED API
- [ ] Deny all other inbound traffic

### Update Server 3 Security Group
- [ ] Add rule to Server 3: Allow MySQL (3306) from Server 2 private IP

### Software Installation
- [ ] System updated (`apt update && apt upgrade`)
- [ ] Apache installed (`apt install apache2`)
- [ ] PHP installed (`apt install php libapache2-mod-php`)
- [ ] PHP extensions installed (`apt install php-mysql php-curl php-json`)
- [ ] Apache enabled and started
- [ ] Apache serving default page verified

### Application Deployment
- [ ] Application files uploaded to server
- [ ] Files extracted to `/var/www/html/`
- [ ] Directory structure verified:
  - [ ] `/var/www/html/api/search.php`
  - [ ] `/var/www/html/api/search-json.php`
  - [ ] `/var/www/html/includes/db.php`
  - [ ] `/var/www/html/includes/mortgage.php`
  - [ ] `/var/www/html/includes/fred.php`
  - [ ] `/var/www/html/test.php`
- [ ] File permissions set (`chown -R www-data:www-data`)
- [ ] File permissions verified (`chmod -R 755`)

### Configuration
- [ ] `includes/db.php` edited with Server 3 private IP
- [ ] `includes/db.php` edited with database password
- [ ] Database connection tested: `php -r "require 'includes/db.php'; print_r(testDatabaseConnection());"`
- [ ] `includes/fred.php` edited with FRED API key
- [ ] FRED API tested: `php -r "require 'includes/fred.php'; print_r(testFredAPI());"`
- [ ] Cache directory created and writable (`/tmp`)

### Testing
- [ ] Accessed test page: `http://SERVER2_PUBLIC_IP/test.php`
- [ ] All test sections show ✅
- [ ] PHP configuration test passed
- [ ] Database connection test passed
- [ ] FRED API test passed
- [ ] Mortgage calculation test passed
- [ ] API endpoints test passed
- [ ] HTML endpoint tested manually
- [ ] JSON endpoint tested manually
- [ ] Sample query returns expected results

---

## Server 1 (Web Tier) - Deploy Third

### EC2 Instance
- [ ] Instance launched (t2.micro, Ubuntu 20.04)
- [ ] Private IP address noted: `________________`
- [ ] **Public IP address noted: `________________`** ← This is the main application URL
- [ ] Elastic IP allocated and associated
- [ ] Security group created (sg-web-tier)
- [ ] SSH key pair assigned
- [ ] Instance in public subnet

### Security Group Configuration
- [ ] Allow SSH (22) from admin IP
- [ ] Allow HTTP (80) from Internet (0.0.0.0/0)
- [ ] Allow HTTPS (443) from Internet (0.0.0.0/0) - optional
- [ ] Allow outbound HTTP (80) to Server 2 private IP

### Update Server 2 Security Group
- [ ] Add rule to Server 2: Allow HTTP (80) from Server 1 private IP

### Software Installation
- [ ] System updated (`apt update && apt upgrade`)
- [ ] Apache installed (`apt install apache2`)
- [ ] Apache enabled and started
- [ ] Apache serving default page verified

### Web Files Deployment
- [ ] Web files uploaded to server
- [ ] Default Apache page removed
- [ ] Files extracted to `/var/www/html/`
- [ ] Directory structure verified:
  - [ ] `/var/www/html/index.html`
  - [ ] `/var/www/html/styles.css`
  - [ ] `/var/www/html/script.js`
- [ ] File permissions set (`chown -R www-data:www-data`)
- [ ] File permissions verified (`chmod -R 755`)

### Configuration
- [ ] `script.js` edited with Server 2 URL
- [ ] Server 2 URL format: `http://SERVER2_PRIVATE_IP`
- [ ] Configuration verified (no typos in IP address)

### Testing
- [ ] Accessed in browser: `http://SERVER1_PUBLIC_IP`
- [ ] HomeFind page loads correctly
- [ ] CSS styling applied correctly
- [ ] Form displays properly
- [ ] Browser console shows no errors
- [ ] Sample search performed:
  - [ ] Zip: 90210
  - [ ] Income: 100000
  - [ ] Beds: 3
  - [ ] Baths: 2
- [ ] Results displayed correctly
- [ ] Affordability badges showing properly
- [ ] Payment calculations visible
- [ ] "View as JSON" button works

---

## Security Hardening

### Server 3 (Database)
- [ ] Firewall enabled (`ufw enable`)
- [ ] Only necessary ports open
- [ ] MySQL root login disabled remotely
- [ ] Remove temporary admin MySQL access rule
- [ ] Strong database password in use
- [ ] Test database access from Server 2 only

### Server 2 (Application)
- [ ] Firewall enabled (`ufw enable`)
- [ ] Only necessary ports open
- [ ] **test.php deleted** (IMPORTANT!)
- [ ] PHP display_errors turned off in production
- [ ] Error logging configured
- [ ] FRED API key not exposed in public files

### Server 1 (Web)
- [ ] Firewall enabled (`ufw enable`)
- [ ] Only necessary ports open
- [ ] No sensitive information in JavaScript
- [ ] CORS headers configured properly
- [ ] No debug code in production

### AWS Security
- [ ] Security groups follow least privilege principle
- [ ] No unnecessary inbound rules
- [ ] Database not accessible from Internet
- [ ] Application tier not directly accessible from Internet
- [ ] SSH access restricted to admin IP only
- [ ] Elastic IP associated with Server 1
- [ ] VPC flow logs enabled (optional)
- [ ] CloudTrail enabled (optional)

---

## Final Testing

### End-to-End Tests
- [ ] Access application via Server 1 public IP
- [ ] Test search with zip 90210 (Beverly Hills)
- [ ] Test search with zip 85001 (Phoenix)
- [ ] Test search with zip 60601 (Chicago)
- [ ] Test with different income levels
- [ ] Test with different bed/bath requirements
- [ ] Verify affordable vs not affordable badges
- [ ] Verify calculations are correct
- [ ] Test JSON endpoint from browser
- [ ] Test with invalid inputs (error handling)
- [ ] Test with non-existent zip code
- [ ] Refresh page and test again (caching)

### Performance Tests
- [ ] Page load time < 3 seconds
- [ ] Search results appear < 2 seconds
- [ ] No timeouts or errors
- [ ] Multiple searches work consistently
- [ ] FRED API caching working (check Server 2 logs)

### Browser Compatibility
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browser (iOS/Android)

---

## Documentation

### Team Documentation
- [ ] Server IP addresses documented
- [ ] Passwords stored securely (password manager)
- [ ] FRED API key documented
- [ ] Database credentials documented
- [ ] SSH key location documented
- [ ] Architecture diagram reviewed with team
- [ ] API documentation shared with team

### Application Documentation
- [ ] README.md updated with actual IP addresses
- [ ] SETUP.md reviewed for accuracy
- [ ] API_DOCUMENTATION.md shared
- [ ] NETWORK_DIAGRAM.md reflects actual deployment
- [ ] Calculation assumptions documented

---

## Monitoring and Maintenance

### Logging Setup
- [ ] Apache logs configured on all servers
- [ ] PHP error logging enabled
- [ ] MySQL slow query log enabled
- [ ] Log rotation configured
- [ ] CloudWatch agent installed (optional)

### Backup Setup
- [ ] Database backup script created
- [ ] Backup schedule configured (cron)
- [ ] Backup storage configured (S3)
- [ ] Backup restoration tested
- [ ] AMI snapshots created for all instances

### Monitoring
- [ ] CloudWatch basic monitoring enabled
- [ ] CloudWatch detailed monitoring enabled (optional)
- [ ] Alarms configured for:
  - [ ] High CPU usage
  - [ ] High disk usage
  - [ ] HTTP 5xx errors
  - [ ] Database connection failures

---

## Additional Features (Optional)

Choose 2+ from the following:

### Feature 1: Full Network Diagram
- [ ] Network diagram created (Visio, draw.io, etc.)
- [ ] Diagram shows all 3 tiers
- [ ] Security groups documented
- [ ] IP addresses labeled
- [ ] External API connection shown
- [ ] Data flow arrows included

### Feature 2: SSL/HTTPS
- [ ] Domain name purchased and configured
- [ ] Let's Encrypt certificate installed
- [ ] Apache configured for HTTPS
- [ ] HTTP to HTTPS redirect configured
- [ ] Certificate auto-renewal configured

### Feature 3: Load Balancer
- [ ] Application Load Balancer created
- [ ] Target group configured
- [ ] Health checks configured
- [ ] Multiple Server 1 instances deployed
- [ ] DNS points to load balancer

### Feature 4: Monitoring Dashboard
- [ ] CloudWatch dashboard created
- [ ] Metrics for all 3 servers
- [ ] Custom metrics configured
- [ ] Alarms visualized
- [ ] Dashboard shared with team

### Feature 5: CI/CD Pipeline
- [ ] Git repository created
- [ ] GitHub Actions or CodePipeline configured
- [ ] Automated testing on push
- [ ] Automated deployment configured
- [ ] Rollback procedure documented

### Feature 6: Additional API Features
- [ ] Pagination implemented
- [ ] Sorting options added
- [ ] Price range filtering added
- [ ] Property type filtering added
- [ ] Saved searches functionality

---

## Go-Live Checklist

### Pre-Launch
- [ ] All tests passed
- [ ] Team members can access application
- [ ] Documentation complete
- [ ] Backups configured and tested
- [ ] Monitoring active
- [ ] test.php deleted from Server 2

### Launch
- [ ] Application URL shared with users
- [ ] Team monitoring for issues
- [ ] Initial traffic tested
- [ ] Error logs monitored
- [ ] Performance metrics checked

### Post-Launch
- [ ] User feedback collected
- [ ] Performance data reviewed
- [ ] Any issues documented and resolved
- [ ] Maintenance plan established
- [ ] Cost monitoring configured

---

## Instructor Submission Checklist

### Required Items
- [ ] Application URL (Server 1 public IP)
- [ ] Full network diagram
- [ ] Architecture documentation
- [ ] Calculation assumptions documented
- [ ] External API integration demonstrated
- [ ] 3-tier architecture clearly separated
- [ ] Database access properly restricted
- [ ] HTML and JSON endpoints working
- [ ] Two additional features implemented

### Demonstration
- [ ] Can search for homes by zip code
- [ ] Can search by bedrooms/bathrooms
- [ ] Affordability calculations working
- [ ] Results show proper formatting
- [ ] JSON endpoint accessible
- [ ] External API (FRED) data visible
- [ ] Database queries functioning
- [ ] Proper security groups configured

---

## Troubleshooting Reference

Common issues and solutions are documented in SETUP.md under the "Troubleshooting" section.

If stuck, verify in this order:
1. Server 3: Database accessible?
2. Server 2: Can connect to database?
3. Server 2: Can connect to FRED API?
4. Server 1: Can connect to Server 2?
5. Security Groups: All rules correct?

---

## Success Criteria

✅ **Application is successfully deployed when:**
- User can access Server 1 via public IP
- User can search homes by zip code and criteria
- Results show homes with accurate payment calculations
- Affordable vs not affordable is clearly indicated
- Mortgage rate from FRED API is current
- JSON endpoint returns proper data
- Database is secure (not publicly accessible)
- All security groups properly configured
- Documentation is complete

---

**Deployment Date:** _______________  
**Deployed By:** _______________  
**Application URL:** http://_______________  
**Team Members:** _______________

---

Good luck with your deployment! 🚀
