-- Server 3: Database Schema
-- Housing Database for Home Affordability Application

-- Create database
CREATE DATABASE IF NOT EXISTS housing_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE housing_db;

-- Drop table if exists (for clean setup)
DROP TABLE IF EXISTS listings;

-- Create listings table
CREATE TABLE listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state CHAR(2) NOT NULL,
    zipcode CHAR(5) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms DECIMAL(3, 1) NOT NULL,
    sqft INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zipcode (zipcode),
    INDEX idx_bedrooms (bedrooms),
    INDEX idx_bathrooms (bathrooms),
    INDEX idx_price (price),
    INDEX idx_search (zipcode, bedrooms, bathrooms)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create application user
-- IMPORTANT: Change the password and IP address before deployment
CREATE USER IF NOT EXISTS 'appuser'@'%' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT SELECT ON housing_db.listings TO 'appuser'@'%';
FLUSH PRIVILEGES;

-- Display table structure
DESCRIBE listings;

-- Show indexes
SHOW INDEX FROM listings;

-- Count records (will be 0 until sample data is loaded)
SELECT COUNT(*) as total_listings FROM listings;
