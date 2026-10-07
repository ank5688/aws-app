# API Documentation

## Overview

Server 2 (Application Tier) exposes two API endpoints for querying home affordability data.

**Base URL**: `http://YOUR_SERVER2_IP`

---

## Endpoints

### 1. Search Homes (HTML Response)

Returns formatted HTML with home listings and affordability calculations.

**Endpoint**: `/api/search.php`

**Method**: GET

**Parameters**:

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `zip` | string | Yes | 5-digit U.S. zip code | `90210` |
| `income` | number | Yes | Annual gross income in USD | `100000` |
| `beds` | integer | Yes | Minimum number of bedrooms (1-5) | `3` |
| `baths` | number | Yes | Minimum number of bathrooms (1-4) | `2` |

**Example Request**:
```
GET /api/search.php?zip=90210&income=100000&beds=3&baths=2
```

**Response**: HTML content suitable for embedding in web page

**Response Structure**:
```html
<div class="results-summary">
    <!-- Search criteria and summary -->
</div>

<div class="listing-card affordable">
    <!-- Individual listing with payment breakdown -->
</div>

<!-- More listing cards... -->
```

---

### 2. Search Homes (JSON Response)

Returns the same data as search.php but in JSON format.

**Endpoint**: `/api/search-json.php`

**Method**: GET

**Parameters**: Same as search.php

**Example Request**:
```
GET /api/search-json.php?zip=90210&income=100000&beds=3&baths=2
```

**Response Format**:
```json
{
    "success": true,
    "data": {
        "mortgage_rate": 6.75,
        "total_listings": 5,
        "affordable_count": 2,
        "listings": [
            {
                "id": 1,
                "address": "789 Sunset Blvd",
                "city": "Beverly Hills",
                "state": "CA",
                "zipcode": "90210",
                "price": 1950000,
                "bedrooms": 3,
                "bathrooms": 2.5,
                "sqft": 2400,
                "down_payment": 390000,
                "loan_amount": 1560000,
                "monthly_pi": 10506.73,
                "monthly_tax": 1950.00,
                "monthly_insurance": 100,
                "monthly_payment": 12556.73,
                "max_affordable": 2333.33,
                "is_affordable": false,
                "affordability_ratio": 150.7
            }
        ]
    },
    "error": null,
    "metadata": {
        "timestamp": "2026-10-07 14:30:00",
        "query": {
            "zipcode": "90210",
            "annual_income": 100000,
            "bedrooms": 3,
            "bathrooms": 2
        }
    }
}
```

**Response Fields**:

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Whether the request was successful |
| `data` | object | Response data (null if error) |
| `data.mortgage_rate` | number | Current 30-year mortgage rate (%) |
| `data.total_listings` | integer | Number of matching listings |
| `data.affordable_count` | integer | Number of affordable listings |
| `data.listings` | array | Array of listing objects |
| `error` | string | Error message (null if successful) |
| `metadata` | object | Request metadata |

**Listing Object Fields**:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Listing database ID |
| `address` | string | Street address |
| `city` | string | City name |
| `state` | string | Two-letter state code |
| `zipcode` | string | 5-digit zip code |
| `price` | number | Purchase price in USD |
| `bedrooms` | integer | Number of bedrooms |
| `bathrooms` | number | Number of bathrooms |
| `sqft` | integer | Square footage |
| `down_payment` | number | Required down payment (20% of price) |
| `loan_amount` | number | Mortgage loan amount (80% of price) |
| `monthly_pi` | number | Monthly principal & interest payment |
| `monthly_tax` | number | Monthly property tax (1.2% annually) |
| `monthly_insurance` | number | Monthly home insurance |
| `monthly_payment` | number | Total monthly payment (P&I + tax + insurance) |
| `max_affordable` | number | Maximum affordable payment (28% of monthly income) |
| `is_affordable` | boolean | Whether listing is affordable for buyer |
| `affordability_ratio` | number | Payment as percentage of income |

---

## Error Responses

### Validation Error (JSON endpoint)
```json
{
    "success": false,
    "data": null,
    "error": "Invalid zip code. Please provide a 5-digit zip code.",
    "metadata": {
        "timestamp": "2026-10-07 14:30:00",
        "query": {
            "zipcode": "abc",
            "annual_income": 100000,
            "bedrooms": 3,
            "bathrooms": 2
        }
    }
}
```

### Server Error (JSON endpoint)
```json
{
    "success": false,
    "data": null,
    "error": "An error occurred while processing your request.",
    "metadata": {
        "timestamp": "2026-10-07 14:30:00",
        "query": {
            "zipcode": "90210",
            "annual_income": 100000,
            "bedrooms": 3,
            "bathrooms": 2
        }
    }
}
```

### No Results
```json
{
    "success": true,
    "data": {
        "mortgage_rate": 6.75,
        "total_listings": 0,
        "affordable_count": 0,
        "listings": []
    },
    "error": null,
    "metadata": {
        "timestamp": "2026-10-07 14:30:00",
        "query": {
            "zipcode": "99999",
            "annual_income": 100000,
            "bedrooms": 3,
            "bathrooms": 2
        }
    }
}
```

---

## Calculation Details

### Down Payment
```
Down Payment = Purchase Price × 0.20 (20%)
```

### Loan Amount
```
Loan Amount = Purchase Price - Down Payment
Loan Amount = Purchase Price × 0.80 (80%)
```

### Monthly Principal & Interest
```
M = P[r(1+r)^n] / [(1+r)^n - 1]

Where:
  M = Monthly payment
  P = Loan amount
  r = Monthly interest rate (annual rate / 12)
  n = Number of payments (30 years × 12 = 360)
```

### Monthly Property Tax
```
Monthly Tax = (Purchase Price × 0.012) / 12
            = Purchase Price × 0.001 (0.1% monthly)
```

### Monthly Insurance
```
Fixed at $100/month (average estimate)
```

### Total Monthly Payment
```
Total = Principal & Interest + Property Tax + Insurance
```

### Maximum Affordable Payment
```
Max Affordable = (Annual Income / 12) × 0.28 (28%)
```

### Affordability Determination
```
Is Affordable = (Total Monthly Payment ≤ Max Affordable Payment)
```

---

## External API Integration

### FRED (Federal Reserve Economic Data)

The application integrates with the FRED API to retrieve current mortgage rates.

**Endpoint**: `https://api.stlouisfed.org/fred/series/observations`

**Series**: `MORTGAGE30US` (30-Year Fixed Rate Mortgage Average)

**Update Frequency**: Weekly (Thursday afternoons)

**Caching**: Rates are cached for 1 hour to reduce API calls

**Fallback**: If FRED API is unavailable, system uses fallback rate of 6.75%

---

## Rate Limiting

Currently, no rate limiting is implemented. For production deployment, consider:

- Implementing request throttling per IP
- Adding API key authentication
- Caching responses for identical queries

---

## CORS (Cross-Origin Resource Sharing)

Both endpoints include:
```php
header('Access-Control-Allow-Origin: *');
```

This allows requests from any origin. For production, restrict to specific domains:
```php
header('Access-Control-Allow-Origin: http://server1-ip');
```

---

## Sample cURL Commands

### Test HTML endpoint:
```bash
curl "http://SERVER2_IP/api/search.php?zip=90210&income=100000&beds=3&baths=2"
```

### Test JSON endpoint:
```bash
curl "http://SERVER2_IP/api/search-json.php?zip=90210&income=100000&beds=3&baths=2" \
  -H "Accept: application/json" | jq
```

### Test with different parameters:
```bash
# Affordable homes in Phoenix
curl "http://SERVER2_IP/api/search-json.php?zip=85001&income=75000&beds=2&baths=2" | jq

# Luxury homes in Beverly Hills
curl "http://SERVER2_IP/api/search-json.php?zip=90210&income=200000&beds=4&baths=3" | jq

# Budget search in Austin
curl "http://SERVER2_IP/api/search-json.php?zip=78701&income=50000&beds=1&baths=1" | jq
```

---

## Available Test Zip Codes

The following zip codes have sample data:

| Zip Code | City | State | Listings | Price Range |
|----------|------|-------|----------|-------------|
| 90210 | Beverly Hills | CA | 5 | $1.95M - $4.2M |
| 94102 | San Francisco | CA | 5 | $950K - $2.1M |
| 10001 | New York | NY | 5 | $950K - $3.1M |
| 78701 | Austin | TX | 5 | $475K - $1.05M |
| 98101 | Seattle | WA | 5 | $650K - $1.85M |
| 33101 | Miami | FL | 5 | $525K - $1.65M |
| 60601 | Chicago | IL | 5 | $395K - $1.15M |
| 80202 | Denver | CO | 5 | $425K - $1.25M |
| 02101 | Boston | MA | 5 | $625K - $1.85M |
| 85001 | Phoenix | AZ | 5 | $245K - $675K |
| 97201 | Portland | OR | 5 | $395K - $1.13M |

---

## Performance Considerations

- **Database Queries**: Indexed on zipcode, bedrooms, bathrooms for fast lookups
- **FRED API**: Cached for 1 hour to minimize external calls
- **Response Time**: Typical response < 200ms for cached mortgage rates
- **Concurrent Users**: Handles 50+ concurrent requests on t2.micro

---

## Security Notes

1. **SQL Injection**: All queries use prepared statements with bound parameters
2. **Input Validation**: All parameters validated before processing
3. **Error Handling**: Sensitive error details logged, generic messages returned to users
4. **Database Access**: Application user has SELECT-only permissions
5. **API Keys**: FRED API key stored in server-side configuration file

---

## Future Enhancements

- [ ] Add pagination for large result sets
- [ ] Implement sorting (price, affordability, etc.)
- [ ] Add filtering (price range, sqft range)
- [ ] Support for FHA loans (lower down payment)
- [ ] Include HOA fees in calculations
- [ ] Add favorite/save functionality with user accounts
- [ ] Real-time updates when new listings added
- [ ] Email notifications for new affordable listings
