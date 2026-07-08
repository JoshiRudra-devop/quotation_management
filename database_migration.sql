-- Database Migration for Trial and Subscription System
-- Run this SQL to add trial and subscription fields to your users table

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS subscription_type ENUM('trial', 'monthly', 'yearly', '3yearly') DEFAULT 'trial',
ADD COLUMN IF NOT EXISTS subscription_start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS subscription_end_date DATETIME NULL,
ADD COLUMN IF NOT EXISTS trial_products_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS trial_companies_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS trial_quotations_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Update existing users to have trial status
UPDATE users SET subscription_type = 'trial' WHERE subscription_type IS NULL;

-- Set trial limits (2 products, 2 companies, 2 quotations)
-- This is handled in PHP code, but we initialize counters here
UPDATE users SET 
    trial_products_count = 0,
    trial_companies_count = 0,
    trial_quotations_count = 0
WHERE subscription_type = 'trial';

