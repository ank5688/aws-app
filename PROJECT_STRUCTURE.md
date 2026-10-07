# Project Structure

## Directory Layout

```
aws-app/
│
├── README.md                          # Project overview and quick start
├── PROJECT_STRUCTURE.md               # This file
├── .gitignore                         # Git ignore patterns
│
├── docs/                              # Documentation
│   ├── SETUP.md                       # Detailed setup instructions
│   ├── API_DOCUMENTATION.md           # API endpoint documentation
│   ├── NETWORK_DIAGRAM.md             # Architecture and network details
│   ├── DEPLOYMENT_CHECKLIST.md        # Step-by-step deployment checklist
│   └── CONFIGURATION_TEMPLATE.md      # Template for tracking deployment config
│
├── server1/                           # Web/Presentation Tier
│   ├── index.html                     # Main user interface
│   ├── styles.css                     # Zillow-inspired styling
│   └── script.js                      # Client-side JavaScript
│
├── server2/                           # Application/Middleware Tier
│   ├── api/
│   │   ├── search.php                 # Main search endpoint (HTML response)
│   │   └── search-json.php            # JSON endpoint
│   │
│   ├── includes/
│   │   ├── db.php                     # Database connection to Server 3
│   │   ├── mortgage.php               # Mortgage calculation library
│   │   └── fred.php                   # FRED API integration
│   │
│   └── test.php                       # System test page (DELETE before production)
│
└── server3/                           # Database Tier
    ├── schema.sql                     # Database schema definition
    └── sample_data.sql                # Sample housing listings (50 records)
```

---

## File Descriptions

### Root Level

**README.md**
- Project purpose and overview
- Architecture summary
- Calculation assumptions
- Quick setup guide
- Request flow description

**.gitignore**
- Excludes sensitive configuration files
- Excludes temporary and cache files
- Excludes IDE and OS-specific files

---

### docs/ Directory

**SETUP.md** (Comprehensive deployment guide)
- Prerequisites and requirements
- Step-by-step setup for each server
- Security configuration
- Testing procedures
- Troubleshooting guide

**API_DOCUMENTATION.md**
- Complete API reference
- Request/response formats
- Parameter descriptions
- Calculation formulas
- Error handling
- Sample queries

**NETWORK_DIAGRAM.md**
- High-level architecture diagram
- Detailed component breakdown
- Security group configurations
- Data flow description
- Network addressing
- Monitoring and logging setup

**DEPLOYMENT_CHECKLIST.md**
- Pre-deployment checklist
- Step-by-step deployment tasks
- Security hardening steps
- Testing procedures
- Go-live checklist
- Troubleshooting reference

**CONFIGURATION_TEMPLATE.md**
- Template for documenting deployment
- Server information tracking
- Database credentials template
- Security group documentation
- URL and endpoint reference
- Team information

---

### server1/ Directory (Web Tier)

**Purpose**: Serve user interface to end users

**index.html** (8.7 KB)
- User input form
- Results display container
- Loading and error states
- Responsive layout
- Accessibility features

**styles.css** (7.2 KB)
- Zillow-inspired design
- Responsive CSS grid
- Affordability badge styling
- Card-based layouts
- Mobile-friendly media queries

**script.js** (4.1 KB)
- Form validation
- AJAX calls to Server 2
- Results rendering
- Error handling
- JSON endpoint access

**Deployment**:
```bash
# Deploy to /var/www/html/ on Server 1
sudo cp * /var/www/html/
```

---

### server2/ Directory (Application Tier)

**Purpose**: Business logic, database queries, API integration

#### api/ Subdirectory

**search.php** (6.8 KB)
- Main search endpoint
- Accepts: zip, income, beds, baths
- Returns: Formatted HTML
- Validates input
- Queries database
- Calls FRED API
- Calculates payments
- Determines affordability

**search-json.php** (4.2 KB)
- JSON version of search.php
- Same business logic
- Returns: Structured JSON
- Includes metadata
- Error responses in JSON

#### includes/ Subdirectory

**db.php** (1.4 KB)
- Database connection management
- MySQLi connection to Server 3
- Connection pooling
- Error handling
- Test connection function

**⚠️ MUST CONFIGURE**: Update DB_HOST and DB_PASS

**mortgage.php** (3.9 KB)
- Monthly payment calculations
- Affordability determination
- Down payment calculation
- Tax and insurance inclusion
- Payment breakdown formatting

**Calculation Details**:
- 20% down payment
- 30-year fixed mortgage
- 1.2% annual property tax
- $100/month insurance
- 28% affordability rule

**fred.php** (3.5 KB)
- FRED API integration
- Current mortgage rate retrieval
- Response caching (1 hour)
- Fallback rate handling
- Error recovery

**⚠️ MUST CONFIGURE**: Update FRED_API_KEY

**test.php** (5.6 KB)
- System health check page
- Tests PHP configuration
- Tests database connection
- Tests FRED API
- Tests mortgage calculations
- Tests API endpoints

**⚠️ DELETE BEFORE PRODUCTION**

**Deployment**:
```bash
# Deploy to /var/www/html/ on Server 2
sudo cp -r api includes test.php /var/www/html/
```

---

### server3/ Directory (Database Tier)

**Purpose**: Store and serve housing listing data

**schema.sql** (1.2 KB)
- Creates `housing_db` database
- Creates `listings` table
- Defines indexes for performance
- Creates `appuser` with SELECT-only permissions

**Fields**:
- id (primary key)
- address, city, state, zipcode
- price, bedrooms, bathrooms, sqft
- created_at, updated_at

**Indexes**:
- zipcode (main search criteria)
- bedrooms, bathrooms (filters)
- Composite index on (zipcode, bedrooms, bathrooms)

**sample_data.sql** (8.9 KB)
- 50 realistic housing listings
- 11 major U.S. zip codes
- Price range: $245K - $4.2M
- Beds: 1-5, Baths: 1-4
- Sqft: 750-5000

**Zip Codes Included**:
- 90210 (Beverly Hills, CA)
- 94102 (San Francisco, CA)
- 10001 (New York, NY)
- 78701 (Austin, TX)
- 98101 (Seattle, WA)
- 33101 (Miami, FL)
- 60601 (Chicago, IL)
- 80202 (Denver, CO)
- 02101 (Boston, MA)
- 85001 (Phoenix, AZ)
- 97201 (Portland, OR)

**Deployment**:
```bash
# Deploy on Server 3
mysql -u root -p < schema.sql
mysql -u root -p < sample_data.sql
```

---

## Technology Stack

### Server 1 (Web Tier)
- **OS**: Ubuntu 20.04 LTS
- **Web Server**: Apache 2.4
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Responsive**: Mobile-friendly design

### Server 2 (Application Tier)
- **OS**: Ubuntu 20.04 LTS
- **Web Server**: Apache 2.4
- **Language**: PHP 7.4+
- **Extensions**: php-mysql, php-curl, php-json
- **External API**: FRED (Federal Reserve Economic Data)

### Server 3 (Database Tier)
- **OS**: Ubuntu 20.04 LTS
- **Database**: MySQL 8.0
- **Storage Engine**: InnoDB
- **Character Set**: utf8mb4

---

## Data Flow Summary

```
1. User visits Server 1 (http://SERVER1_PUBLIC_IP)
2. User submits search form
3. Server 1 JavaScript calls Server 2 API
   GET /api/search.php?zip=...&income=...&beds=...&baths=...
4. Server 2 queries Server 3 MySQL database
   SELECT * FROM listings WHERE zipcode=? AND bedrooms>=? AND bathrooms>=?
5. Server 2 calls FRED API for mortgage rate
   GET https://api.stlouisfed.org/fred/series/observations?series_id=MORTGAGE30US
6. Server 2 calculates monthly payments and affordability
7. Server 2 returns formatted HTML to Server 1
8. Server 1 displays results to user
```

**Average Response Time**: 200-500ms

---

## Security Architecture

### Network Segmentation
- **Public Subnet**: Server 1 only
- **Private Subnet**: Server 2, Server 3
- **Internet Access**: Server 1 public, Server 2 via NAT (optional), Server 3 none

### Access Control
- **Server 1**: Accessible from Internet (HTTP/HTTPS)
- **Server 2**: Accessible from Server 1 only
- **Server 3**: Accessible from Server 2 only (+ admin for setup)

### Database Security
- **Application User**: SELECT-only permissions
- **No Root Access**: From application
- **Prepared Statements**: SQL injection prevention
- **Network Restricted**: Security group rules

### API Security
- **Input Validation**: All parameters validated
- **Error Handling**: Generic error messages
- **No Credentials**: In client-side code
- **HTTPS**: For external API calls

---

## Development Workflow

### Initial Development
1. Read all documentation (README, SETUP, API_DOCUMENTATION)
2. Review architecture (NETWORK_DIAGRAM)
3. Test locally if possible (requires local MySQL)
4. Deploy to AWS following SETUP.md
5. Use DEPLOYMENT_CHECKLIST.md

### Testing
1. Deploy Server 3 first, test database
2. Deploy Server 2, use test.php
3. Deploy Server 1, test end-to-end
4. Verify all security groups
5. Test with multiple zip codes

### Maintenance
1. Monitor logs on all servers
2. Backup database regularly
3. Update FRED API cache if needed
4. Monitor AWS costs
5. Apply security updates

---

## Deployment Order

**Always deploy in this order:**

1. **Server 3** (Database) - Foundation
   - Launch instance
   - Install MySQL
   - Create database and user
   - Load sample data
   - Test local connection

2. **Server 2** (Application) - Middle tier
   - Launch instance
   - Install Apache + PHP
   - Deploy application files
   - Configure database connection
   - Configure FRED API
   - Test with test.php

3. **Server 1** (Web) - Public face
   - Launch instance
   - Install Apache
   - Deploy web files
   - Configure Server 2 URL
   - Test end-to-end

---

## File Sizes

```
Total Project Size: ~45 KB (code only)

server1/:           ~20 KB
  index.html        8.7 KB
  styles.css        7.2 KB
  script.js         4.1 KB

server2/:           ~28 KB
  api/search.php        6.8 KB
  api/search-json.php   4.2 KB
  includes/db.php       1.4 KB
  includes/mortgage.php 3.9 KB
  includes/fred.php     3.5 KB
  test.php              5.6 KB

server3/:           ~10 KB
  schema.sql        1.2 KB
  sample_data.sql   8.9 KB

docs/:              ~65 KB
  SETUP.md              22 KB
  API_DOCUMENTATION.md  18 KB
  NETWORK_DIAGRAM.md    15 KB
  DEPLOYMENT_CHECKLIST  7 KB
  CONFIGURATION_TEMPLATE 3 KB
```

---

## Version Control

### Recommended Git Workflow

```bash
# Initialize repository
git init
git add .
git commit -m "Initial commit: 3-tier home affordability app"

# Create development branch
git checkout -b development

# Make changes
git add .
git commit -m "Description of changes"

# Push to remote
git push origin development

# Merge to main when ready for deployment
git checkout main
git merge development
git push origin main
```

### Branches Strategy
- `main` - Production-ready code
- `development` - Active development
- `feature/*` - Feature branches
- `hotfix/*` - Production fixes

---

## Additional Resources

### External Documentation
- **FRED API**: https://fred.stlouisfed.org/docs/api/
- **MySQL Docs**: https://dev.mysql.com/doc/
- **PHP Docs**: https://www.php.net/docs.php
- **Apache Docs**: https://httpd.apache.org/docs/

### AWS Documentation
- **EC2**: https://docs.aws.amazon.com/ec2/
- **VPC**: https://docs.aws.amazon.com/vpc/
- **Security Groups**: https://docs.aws.amazon.com/vpc/latest/userguide/VPC_SecurityGroups.html

---

## Future Enhancements

Potential features to add:
- [ ] User authentication and saved searches
- [ ] Email notifications for new listings
- [ ] FHA loan calculator (lower down payment)
- [ ] Property comparison tool
- [ ] Map view with listings
- [ ] Mortgage payment scheduler
- [ ] HOA fee inclusion
- [ ] Real-time listings (integrate Zillow API)
- [ ] Mobile app version
- [ ] Export to PDF

---

**Project Version**: 1.0  
**Last Updated**: 2026-10-07  
**Created For**: Cloud Computing Course - 3-Tier Application Project
