# Network Architecture Diagram

## Overview

This document describes the complete network architecture of the Home Affordability Application, including all three tiers, security groups, and data flow.

---

## High-Level Architecture

```
                                    INTERNET
                                       |
                                       |
                    +------------------+------------------+
                    |                                     |
                    |          AWS VPC                    |
                    |      10.0.0.0/16                    |
                    |                                     |
                    |  +-----------------------------+    |
                    |  |  PUBLIC SUBNET             |    |
                    |  |  10.0.1.0/24               |    |
                    |  |                            |    |
                    |  |  +-------------------+     |    |
                    |  |  | SERVER 1          |     |    |
                    |  |  | Web Tier          |     |    |
                    |  |  | Public IP         |     |    |
                    |  |  | 10.0.1.10         |     |    |
                    |  |  | Apache 2.4        |     |    |
                    |  |  | Static HTML/CSS/JS|     |    |
                    |  |  +-------------------+     |    |
                    |  |           |                |    |
                    |  +-----------|----------------+    |
                    |              |                     |
                    |              | HTTP                |
                    |              |                     |
                    |  +-----------|----------------+    |
                    |  |  PRIVATE SUBNET           |    |
                    |  |  10.0.2.0/24              |    |
                    |  |           ↓               |    |
                    |  |  +-------------------+    |    |
                    |  |  | SERVER 2          |    |    |
                    |  |  | Application Tier  |    |    |
                    |  |  | 10.0.2.10         |    |    |
                    |  |  | Apache + PHP 7.4  |    |    |
                    |  |  | Business Logic    |    |    |
                    |  |  +-------------------+    |    |
                    |  |      |              |     |    |
                    |  |      |              |     |    |
                    |  +------|--------------|-----+    |
                    |         |              |          |
                    |   MySQL |              | HTTPS    |
                    |         |              |          |
                    |  +------|--------------|-----+    |
                    |  |  PRIVATE SUBNET    ↓     |    |
                    |  |  10.0.3.0/24             |    |
                    |  |      ↓                   |    |
                    |  |  +-------------------+   |    |
                    |  |  | SERVER 3          |   |    |
                    |  |  | Database Tier     |   |    |
                    |  |  | 10.0.3.10         |   |    |
                    |  |  | MySQL 8.0         |   |    |
                    |  |  | housing_db        |   |    |
                    |  |  +-------------------+   |    |
                    |  |                          |    |
                    |  +--------------------------+    |
                    |                                  |
                    +----------------------------------+
                                       |
                                       | HTTPS
                                       ↓
                        +-------------------------+
                        |   FRED API              |
                        |   (External Service)    |
                        |   api.stlouisfed.org    |
                        +-------------------------+
```

---

## Detailed Component Breakdown

### Server 1 (Web/Presentation Tier)

**Purpose**: Serve user interface to end users

**Instance Details**:
- Type: t2.micro (1 vCPU, 1 GB RAM)
- AMI: Ubuntu 20.04 LTS
- Storage: 10 GB GP2
- Network: Public subnet with Elastic IP

**Software Stack**:
- Operating System: Ubuntu 20.04
- Web Server: Apache 2.4
- Content: Static HTML, CSS, JavaScript

**Inbound Connections**:
- HTTP (80) from Internet (0.0.0.0/0)
- HTTPS (443) from Internet (0.0.0.0/0) [optional]
- SSH (22) from Admin IP

**Outbound Connections**:
- HTTP (80) to Server 2 (10.0.2.10)

**Files Served**:
- `/var/www/html/index.html`
- `/var/www/html/styles.css`
- `/var/www/html/script.js`

---

### Server 2 (Application/Middleware Tier)

**Purpose**: Business logic, database queries, external API integration

**Instance Details**:
- Type: t2.micro (1 vCPU, 1 GB RAM)
- AMI: Ubuntu 20.04 LTS
- Storage: 20 GB GP2
- Network: Private subnet (no public IP)

**Software Stack**:
- Operating System: Ubuntu 20.04
- Web Server: Apache 2.4
- Application: PHP 7.4
- Extensions: php-mysql, php-curl, php-json

**Inbound Connections**:
- HTTP (80) from Server 1 (10.0.1.10)
- SSH (22) from Admin IP

**Outbound Connections**:
- MySQL (3306) to Server 3 (10.0.3.10)
- HTTPS (443) to FRED API (internet)

**API Endpoints**:
- `/api/search.php` - HTML response
- `/api/search-json.php` - JSON response

**Application Components**:
- `api/search.php` - Main search endpoint
- `api/search-json.php` - JSON endpoint
- `includes/db.php` - Database connection
- `includes/mortgage.php` - Payment calculations
- `includes/fred.php` - FRED API integration

---

### Server 3 (Database Tier)

**Purpose**: Store and serve housing listing data

**Instance Details**:
- Type: t2.micro (1 vCPU, 1 GB RAM)
- AMI: Ubuntu 20.04 LTS
- Storage: 20 GB GP2
- Network: Private subnet (no public IP)

**Software Stack**:
- Operating System: Ubuntu 20.04
- Database: MySQL 8.0

**Inbound Connections**:
- MySQL (3306) from Server 2 (10.0.2.10)
- MySQL (3306) from Admin IP (for management)
- SSH (22) from Admin IP

**Outbound Connections**:
- None required

**Database Schema**:
- Database: `housing_db`
- Table: `listings` (50 sample records)
- User: `appuser` (SELECT-only permissions)

---

## Security Groups Configuration

### Security Group 1: Web Tier (sg-web-tier)

| Type | Protocol | Port | Source | Description |
|------|----------|------|--------|-------------|
| Inbound | TCP | 80 | 0.0.0.0/0 | HTTP from Internet |
| Inbound | TCP | 443 | 0.0.0.0/0 | HTTPS from Internet |
| Inbound | TCP | 22 | Admin IP | SSH management |
| Outbound | TCP | 80 | 10.0.2.10/32 | HTTP to Server 2 |
| Outbound | TCP | 443 | 0.0.0.0/0 | HTTPS for updates |

### Security Group 2: Application Tier (sg-app-tier)

| Type | Protocol | Port | Source | Description |
|------|----------|------|--------|-------------|
| Inbound | TCP | 80 | 10.0.1.10/32 | HTTP from Server 1 |
| Inbound | TCP | 22 | Admin IP | SSH management |
| Outbound | TCP | 3306 | 10.0.3.10/32 | MySQL to Server 3 |
| Outbound | TCP | 443 | 0.0.0.0/0 | HTTPS to FRED API |

### Security Group 3: Database Tier (sg-db-tier)

| Type | Protocol | Port | Source | Description |
|------|----------|------|--------|-------------|
| Inbound | TCP | 3306 | 10.0.2.10/32 | MySQL from Server 2 |
| Inbound | TCP | 3306 | Admin IP | MySQL management |
| Inbound | TCP | 22 | Admin IP | SSH management |
| Outbound | ALL | ALL | 0.0.0.0/0 | Allow responses |

---

## Data Flow

### User Search Request Flow

```
1. User → Server 1 (HTTP GET)
   Browser sends form data to public IP
   URL: http://SERVER1_PUBLIC_IP

2. Server 1 → Server 2 (HTTP GET)
   JavaScript calls API with parameters
   URL: http://10.0.2.10/api/search.php?zip=90210&income=100000&beds=3&baths=2

3. Server 2 → Server 3 (MySQL Query)
   PHP queries database with prepared statement
   Query: SELECT * FROM listings WHERE zipcode=? AND bedrooms>=? AND bathrooms>=?

4. Server 3 → Server 2 (Query Results)
   Returns matching listing records

5. Server 2 → FRED API (HTTPS GET)
   PHP retrieves current mortgage rate
   URL: https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US...

6. FRED API → Server 2 (JSON Response)
   Returns current rate data

7. Server 2 (Internal Processing)
   - Calculates monthly payments for each listing
   - Determines affordability based on income
   - Formats results as HTML

8. Server 2 → Server 1 (HTTP Response)
   Returns formatted HTML with results

9. Server 1 → User (HTTP Response)
   Displays results in browser
```

**Total Request Time**: ~200-500ms
- Database query: 10-50ms
- FRED API call: 100-300ms (or 0ms if cached)
- Calculations: 10-50ms
- Network latency: 50-100ms

---

## Network Addressing

| Server | Subnet | Private IP | Public IP | Purpose |
|--------|--------|------------|-----------|---------|
| Server 1 | 10.0.1.0/24 | 10.0.1.10 | Elastic IP | Web Tier |
| Server 2 | 10.0.2.0/24 | 10.0.2.10 | None | App Tier |
| Server 3 | 10.0.3.0/24 | 10.0.3.10 | None | DB Tier |

**VPC CIDR**: 10.0.0.0/16

**Internet Gateway**: Attached to VPC for public subnet

**NAT Gateway**: Optional (in public subnet) for Server 2 updates

**Route Tables**:
- Public: Routes 0.0.0.0/0 to Internet Gateway
- Private: Routes 0.0.0.0/0 to NAT Gateway (optional)

---

## External Dependencies

### FRED API (Federal Reserve Economic Data)

**Purpose**: Retrieve current 30-year mortgage rates

**Endpoint**: `https://api.stlouisfed.org/fred/series/observations`

**Protocol**: HTTPS (443)

**Authentication**: API Key (required)

**Rate Limiting**: 120 requests/minute (free tier)

**Data Update Frequency**: Weekly (Thursdays)

**Caching Strategy**: 1-hour cache to minimize API calls

**Fallback**: 6.75% default rate if API unavailable

---

## Monitoring and Logging

### Application Logs

**Server 1**:
- Apache access log: `/var/log/apache2/access.log`
- Apache error log: `/var/log/apache2/error.log`

**Server 2**:
- Apache access log: `/var/log/apache2/access.log`
- Apache error log: `/var/log/apache2/error.log`
- PHP error log: `/var/log/php/error.log`
- Application log: Custom logging in PHP files

**Server 3**:
- MySQL general log: `/var/log/mysql/mysql.log`
- MySQL error log: `/var/log/mysql/error.log`
- MySQL slow query log: `/var/log/mysql/slow.log`

### Recommended CloudWatch Metrics

- CPU Utilization (all servers)
- Network In/Out (all servers)
- Disk Read/Write (all servers)
- MySQL Queries/second (Server 3)
- HTTP Response Times (Server 1, Server 2)
- HTTP Status Codes (Server 1, Server 2)

---

## Disaster Recovery

### Backup Strategy

**Database (Server 3)**:
- Daily automated backups using `mysqldump`
- Retention: 7 days
- Storage: S3 bucket with versioning

**Application Code (Server 2)**:
- Git repository for version control
- Automated deployment from repository

**Web Files (Server 1)**:
- Static files in Git repository
- Can be redeployed quickly

### High Availability Options

1. **Load Balancer**: Add Application Load Balancer for Server 1
2. **Auto Scaling**: Configure Auto Scaling Groups for Server 1 and 2
3. **RDS**: Migrate Server 3 to RDS Multi-AZ for automatic failover
4. **ElastiCache**: Add Redis/Memcached for session and API caching

---

## Scalability Considerations

### Current Capacity
- **Concurrent Users**: ~50-100
- **Requests/Second**: ~10-20
- **Database Queries/Second**: ~50

### Scaling Options

**Horizontal Scaling**:
1. Add multiple Server 1 instances behind load balancer
2. Add multiple Server 2 instances behind internal load balancer
3. Implement database read replicas for Server 3

**Vertical Scaling**:
1. Upgrade to larger EC2 instance types
2. Add more storage (for database growth)
3. Increase database connection pool size

**Caching**:
1. Add CloudFront CDN for Server 1 static files
2. Implement Redis for API response caching
3. Use MySQL query cache

---

## Cost Estimation

**Monthly AWS Costs** (us-east-1 region):

| Resource | Type | Quantity | Monthly Cost |
|----------|------|----------|--------------|
| EC2 (Server 1) | t2.micro | 1 | $8.50 |
| EC2 (Server 2) | t2.micro | 1 | $8.50 |
| EC2 (Server 3) | t2.micro | 1 | $8.50 |
| EBS Storage | GP2 | 50 GB | $5.00 |
| Elastic IP | - | 1 | $3.60 |
| Data Transfer | - | 10 GB/mo | $0.90 |
| **Total** | | | **~$35/month** |

*Prices as of 2024, may vary by region*

**Additional Costs**:
- FRED API: Free
- Domain name: $10-15/year
- SSL certificate: Free (Let's Encrypt)

---

## Compliance and Security

### Security Best Practices Implemented

✅ Database in private subnet (not internet accessible)  
✅ Application tier in private subnet  
✅ Least privilege database user (SELECT only)  
✅ SQL injection prevention (prepared statements)  
✅ Input validation on all parameters  
✅ Security groups restrict traffic by IP  
✅ No hardcoded credentials (configuration files)  
✅ Error messages don't expose sensitive data  
✅ HTTPS for external API calls  
✅ Regular security updates  

### Additional Security Recommendations

- Enable VPC Flow Logs for network monitoring
- Implement AWS WAF on Server 1
- Use AWS Secrets Manager for database credentials
- Enable CloudTrail for API auditing
- Configure AWS GuardDuty for threat detection
- Implement SSL/TLS for all connections
- Use AWS Systems Manager Session Manager instead of SSH
- Enable encryption at rest for EBS volumes

---

## Testing Endpoints

Use these for testing the complete system:

```bash
# Test Server 1 (from your browser)
http://SERVER1_PUBLIC_IP

# Test Server 2 HTML endpoint (from Server 1 or admin)
curl "http://10.0.2.10/api/search.php?zip=90210&income=100000&beds=3&baths=2"

# Test Server 2 JSON endpoint
curl "http://10.0.2.10/api/search-json.php?zip=90210&income=100000&beds=3&baths=2"

# Test Server 3 database (from Server 2)
mysql -u appuser -p -h 10.0.3.10 housing_db -e "SELECT COUNT(*) FROM listings;"

# Test FRED API (from Server 2)
curl "https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US&api_key=YOUR_KEY&file_type=json&limit=1&sort_order=desc"
```

---

This network diagram represents a production-ready, secure, and scalable 3-tier architecture suitable for educational and small production deployments.
