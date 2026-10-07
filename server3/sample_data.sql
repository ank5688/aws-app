-- Server 3: Sample Housing Data
-- Realistic housing listings for major U.S. zip codes

USE housing_db;

-- Clear existing data
TRUNCATE TABLE listings;

-- Insert sample listings for various zip codes
-- Prices reflect realistic market conditions for each area

-- Los Angeles, CA - 90210 (Beverly Hills)
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('123 Rodeo Dr', 'Beverly Hills', 'CA', '90210', 2850000, 4, 3.5, 3200),
('456 Palm Ave', 'Beverly Hills', 'CA', '90210', 3500000, 5, 4, 4100),
('789 Sunset Blvd', 'Beverly Hills', 'CA', '90210', 1950000, 3, 2.5, 2400),
('321 Beverly Dr', 'Beverly Hills', 'CA', '90210', 4200000, 5, 5, 5000),
('654 Canyon Rd', 'Beverly Hills', 'CA', '90210', 2100000, 3, 3, 2800);

-- San Francisco, CA - 94102
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 Market St', 'San Francisco', 'CA', '94102', 1250000, 2, 2, 1200),
('200 Mission St', 'San Francisco', 'CA', '94102', 1850000, 3, 2.5, 1800),
('300 Howard St', 'San Francisco', 'CA', '94102', 950000, 1, 1, 850),
('400 Folsom St', 'San Francisco', 'CA', '94102', 1650000, 3, 2, 1600),
('500 Harrison St', 'San Francisco', 'CA', '94102', 2100000, 4, 3, 2200);

-- New York, NY - 10001 (Manhattan)
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 W 23rd St', 'New York', 'NY', '10001', 1500000, 2, 2, 1100),
('200 W 24th St', 'New York', 'NY', '10001', 2200000, 3, 2, 1500),
('300 W 25th St', 'New York', 'NY', '10001', 950000, 1, 1, 750),
('400 W 26th St', 'New York', 'NY', '10001', 1850000, 2, 2.5, 1300),
('500 W 27th St', 'New York', 'NY', '10001', 3100000, 4, 3, 2000);

-- Austin, TX - 78701
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 Colorado St', 'Austin', 'TX', '78701', 625000, 2, 2, 1300),
('200 Congress Ave', 'Austin', 'TX', '78701', 850000, 3, 2.5, 1900),
('300 Lavaca St', 'Austin', 'TX', '78701', 475000, 1, 1, 900),
('400 Guadalupe St', 'Austin', 'TX', '78701', 725000, 2, 2, 1500),
('500 Red River St', 'Austin', 'TX', '78701', 1050000, 4, 3, 2400);

-- Seattle, WA - 98101
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 Pike St', 'Seattle', 'WA', '98101', 875000, 2, 2, 1250),
('200 Pine St', 'Seattle', 'WA', '98101', 1200000, 3, 2.5, 1750),
('300 Union St', 'Seattle', 'WA', '98101', 650000, 1, 1, 850),
('400 Madison St', 'Seattle', 'WA', '98101', 1450000, 3, 3, 2000),
('500 Spring St', 'Seattle', 'WA', '98101', 1850000, 4, 3.5, 2600);

-- Miami, FL - 33101
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 Biscayne Blvd', 'Miami', 'FL', '33101', 725000, 2, 2, 1400),
('200 NE 1st Ave', 'Miami', 'FL', '33101', 950000, 3, 2, 1800),
('300 NE 2nd Ave', 'Miami', 'FL', '33101', 525000, 1, 1, 950),
('400 NE 3rd Ave', 'Miami', 'FL', '33101', 1250000, 3, 3, 2200),
('500 NE 4th Ave', 'Miami', 'FL', '33101', 1650000, 4, 3.5, 2800);

-- Chicago, IL - 60601
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 N State St', 'Chicago', 'IL', '60601', 525000, 2, 2, 1200),
('200 N Wabash Ave', 'Chicago', 'IL', '60601', 725000, 3, 2, 1650),
('300 N Michigan Ave', 'Chicago', 'IL', '60601', 395000, 1, 1, 850),
('400 N Dearborn St', 'Chicago', 'IL', '60601', 875000, 3, 2.5, 1900),
('500 N Clark St', 'Chicago', 'IL', '60601', 1150000, 4, 3, 2400);

-- Denver, CO - 80202
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 16th St', 'Denver', 'CO', '80202', 575000, 2, 2, 1300),
('200 17th St', 'Denver', 'CO', '80202', 775000, 3, 2.5, 1800),
('300 18th St', 'Denver', 'CO', '80202', 425000, 1, 1, 900),
('400 19th St', 'Denver', 'CO', '80202', 925000, 3, 3, 2100),
('500 20th St', 'Denver', 'CO', '80202', 1250000, 4, 3.5, 2700);

-- Boston, MA - 02101
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 State St', 'Boston', 'MA', '02101', 875000, 2, 2, 1150),
('200 Congress St', 'Boston', 'MA', '02101', 1250000, 3, 2, 1600),
('300 Federal St', 'Boston', 'MA', '02101', 625000, 1, 1, 800),
('400 Atlantic Ave', 'Boston', 'MA', '02101', 1450000, 3, 2.5, 1900),
('500 Summer St', 'Boston', 'MA', '02101', 1850000, 4, 3, 2400);

-- Phoenix, AZ - 85001
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 N 1st St', 'Phoenix', 'AZ', '85001', 325000, 2, 2, 1400),
('200 N 2nd St', 'Phoenix', 'AZ', '85001', 425000, 3, 2, 1900),
('300 N 3rd St', 'Phoenix', 'AZ', '85001', 245000, 1, 1, 950),
('400 N 4th St', 'Phoenix', 'AZ', '85001', 525000, 3, 2.5, 2200),
('500 N 5th St', 'Phoenix', 'AZ', '85001', 675000, 4, 3, 2800);

-- Portland, OR - 97201
INSERT INTO listings (address, city, state, zipcode, price, bedrooms, bathrooms, sqft) VALUES
('100 SW Broadway', 'Portland', 'OR', '97201', 525000, 2, 2, 1250),
('200 SW Taylor St', 'Portland', 'OR', '97201', 725000, 3, 2.5, 1750),
('300 SW Salmon St', 'Portland', 'OR', '97201', 395000, 1, 1, 850),
('400 SW Madison St', 'Portland', 'OR', '97201', 875000, 3, 3, 2000),
('500 SW Jefferson St', 'Portland', 'OR', '97201', 1125000, 4, 3.5, 2600);

-- Display summary statistics
SELECT 
    state,
    COUNT(*) as listings,
    MIN(price) as min_price,
    AVG(price) as avg_price,
    MAX(price) as max_price
FROM listings
GROUP BY state
ORDER BY state;

SELECT 
    zipcode,
    city,
    state,
    COUNT(*) as listings,
    AVG(price) as avg_price
FROM listings
GROUP BY zipcode, city, state
ORDER BY zipcode;

-- Total count
SELECT COUNT(*) as total_listings FROM listings;
