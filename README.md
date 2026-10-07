# Home Affordability Application - 3-Tier Architecture

## Purpose
Help homebuyers see which houses in a zip code they can afford and what each would cost per month.

## Architecture

### Server 1: Web/Presentation Tier (Public)
- **Technology**: Apache HTTP Server
- **Role**: Presents the user interface
- **Access**: Public IP address
- **Components**:
  - `index.html` - Input form for zip code, income, bedrooms, bathrooms
  - `styles.css` - Zillow-style branding
  - `script.js` - Form submission handler

### Server 2: Middleware/Application Tier
- **Technology**: Apache + PHP 7.4+
- **Role**: Business logic, API integration, calculations
- **Access**: Receives requests from Server 1
- **Components**:
  - `api/search.php` - Main endpoint (returns HTML)
  - `api/search-json.php` - JSON endpoint
  - `includes/db.php` - Database connection
  - `includes/mortgage.php` - Payment calculations
  - `includes/fred.php` - FRED API integration

### Server 3: Database Tier
- **Technology**: MySQL 8.0+
- **Role**: Stores housing listings
- **Access**: Restricted to Server 2 and team workstations only
- **Database**: `housing_db`
- **Table**: `listings`

## Calculation Assumptions

1. **Loan Term**: 30 years (360 months)
2. **Down Payment**: 20% of purchase price
3. **Affordability Rule**: Monthly payment ≤ 28% of gross monthly income
4. **Property Tax**: 1.2% of home value annually (0.1% monthly)
5. **Home Insurance**: $100/month (average estimate)
6. **PMI**: Not included (assumes 20% down payment)

**Monthly Payment Formula**:
```
Loan Amount = Purchase Price × 0.80 (20% down)
Monthly Principal & Interest = Loan × [r(1+r)^n] / [(1+r)^n - 1]
  where r = monthly interest rate, n = 360 months
Property Tax (monthly) = (Purchase Price × 0.012) / 12
Insurance = $100
Total Monthly Payment = P&I + Tax + Insurance
```

**Affordability Check**:
```
Max Affordable Payment = (Annual Income / 12) × 0.28
Affordable = (Total Monthly Payment ≤ Max Affordable Payment)
```

## External API

**FRED (Federal Reserve Economic Data)**
- Endpoint: `https://api.stlouisfed.org/fred/series/observations`
- Series: `MORTGAGE30US` (30-Year Fixed Rate Mortgage Average)
- Returns current mortgage rate
- API Key required (free at https://fred.stlouisfed.org/docs/api/api_key.html)

## Data Source

Housing listings use **generated sample data** based on realistic U.S. housing market patterns. Data includes:
- Price (realistic for each zip code)
- Bedrooms (1-5)
- Bathrooms (1-4)
- Square footage (800-4000 sq ft)
- Zip code (major U.S. cities)
- Address

## Request Flow

1. User visits Server 1's public IP address
2. User enters:
   - Zip code (5 digits)
   - Annual income ($)
   - Desired bedrooms (1-5)
   - Desired bathrooms (1-4)
3. User clicks "Search Homes"
4. Server 1 calls Server 2: `http://server2-ip/api/search.php?zip=...&income=...&beds=...&baths=...`
5. Server 2 queries MySQL on Server 3
6. Server 2 calls FRED API for current mortgage rate
7. Server 2 calculates monthly payment and affordability for each listing
8. Server 2 returns HTML response with results
9. JSON endpoint available: `/api/search-json.php` with same parameters

## Security Requirements

**Server 3 (Database) Security**:
- MySQL configured to accept connections ONLY from:
  - Server 2 private IP
  - Team workstation IPs
- Use AWS Security Groups or iptables rules
- No public access

## Setup Instructions

See `docs/SETUP.md` for detailed installation and deployment steps.

## Additional Features

(To be implemented - choose 2 from Final Assignments list)
1. Full network diagram
2. [Second feature TBD]

## Team Company Context

**Real Estate / Property Search Platform** (Zillow-style)
