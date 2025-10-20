-- SQL script to add customer authentication fields to the customers table

-- Add customer_id (5-digit random ID) and password fields
ALTER TABLE customers 
ADD COLUMN customer_id VARCHAR(5) UNIQUE,
ADD COLUMN customer_password VARCHAR(255);

-- Add index for faster lookups
CREATE INDEX idx_customer_id ON customers(customer_id);

-- Note: Run this script on your database to update the schema
-- You can execute this via phpMyAdmin or command line:
-- mysql -u oyunmenu -p oyunmenu < database_update.sql
