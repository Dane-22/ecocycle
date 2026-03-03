-- Add ecocoins_price column to products table
-- Conversion rate: 1 PHP = 1 EcoCoin

ALTER TABLE `products` ADD COLUMN `ecocoins_price` INT DEFAULT 0 AFTER `price`;

-- Populate ecocoins_price with calculated values (price value as ecocoins)
UPDATE `products` SET `ecocoins_price` = ROUND(`price`) WHERE `ecocoins_price` = 0;
