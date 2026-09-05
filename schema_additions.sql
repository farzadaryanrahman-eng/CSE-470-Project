-- =============================================================
-- schema_additions.sql  (BASE SCHEMA -- run this FIRST)
--
-- Creates every table the PHP pages query that was not already
-- covered by schema_additions_2.sql:
--   consumer, appointments, grooming_appointments,
--   orders, order_items, wishlist, reviews, health_records
--
-- RUN ORDER:
--   1. CREATE DATABASE `kitty-pup-stuffs`;   (name must match DBconnect.php)
--   2. USE `kitty-pup-stuffs`;
--   3. this file
--   4. schema_additions_2.sql
--
-- `role` is deliberately NOT defined on consumer here, because
-- schema_additions_2.sql adds it with an ALTER TABLE.
-- =============================================================

-- Accounts. login.php matches on Email + Password; every other page
-- keys its rows off `Name` via $_SESSION['username'], so Name is UNIQUE
-- to stop two accounts sharing one set of pets/orders/appointments.
CREATE TABLE IF NOT EXISTS consumer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL UNIQUE,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Phone VARCHAR(50),
    Address VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feature 4/5: Vet Appointment Booking (vet.php, appointment_management.php)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    pet_name VARCHAR(255) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    app_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appointments_user (username)
);

-- Feature 6/7: Grooming Appointment Booking (groomer.php, appointment_management.php)
CREATE TABLE IF NOT EXISTS grooming_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    pet_name VARCHAR(255) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    app_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grooming_user (username)
);

-- Feature 17: Order Management (payment.php / stripe_success.php write,
-- order_management.php reads). `payments` in schema_additions_2.sql has a
-- foreign key onto orders(id), which is why orders must be created first.
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user (username)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Feature 11: Wishlist Management.
-- pawmart.php uses INSERT IGNORE to avoid duplicate wishlist rows, which
-- only works if (username, item_name) is a UNIQUE key.
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (username, item_name)
);

-- Feature 14: Product Reviews & Ratings (reviews.php)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviews_item (item_name)
);

-- Feature 10: AI Health Report Summary (health_report.php)
CREATE TABLE IF NOT EXISTS health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    pet_name VARCHAR(255) NOT NULL,
    record_text TEXT NOT NULL,
    record_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_user_pet (username, pet_name)
);
