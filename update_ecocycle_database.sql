-- Update Ecocycle Database for Login Security System
-- Run these commands in phpMyAdmin to add missing columns

-- Add reset columns to buyers table
ALTER TABLE buyers 
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_required TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME NULL;

-- Add reset columns to sellers table  
ALTER TABLE sellers
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_required TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME NULL;

-- Create login_attempts table for tracking failed logins
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_identifier VARCHAR(255) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_login_identifier (login_identifier),
    INDEX idx_attempt_time (attempt_time)
);

-- Verify the changes
SELECT 'buyers table updated' as status;
DESCRIBE buyers;

SELECT 'sellers table updated' as status;
DESCRIBE sellers;

SELECT 'login_attempts table created' as status;
DESCRIBE login_attempts;
