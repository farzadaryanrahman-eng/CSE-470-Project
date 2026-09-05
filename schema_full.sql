-- PawMart full schema for a first-time MySQL setup.
-- phpMyAdmin: Import this file (it creates the database), or run in the mysql client.
-- Existing databases: use schema_additions_video_stripe.sql instead.

CREATE DATABASE IF NOT EXISTS `kitty-pup-stuffs`;
USE `kitty-pup-stuffs`;

CREATE TABLE IF NOT EXISTS consumer (
  Name VARCHAR(255) NOT NULL,
  Email VARCHAR(255) NOT NULL,
  Password VARCHAR(255) NOT NULL,
  Phone VARCHAR(64) DEFAULT NULL,
  Address VARCHAR(255) DEFAULT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'user',
  PRIMARY KEY (Email)
);

CREATE TABLE IF NOT EXISTS pets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  pet_name VARCHAR(255) NOT NULL,
  species VARCHAR(64) DEFAULT NULL,
  breed VARCHAR(64) DEFAULT NULL,
  age INT DEFAULT NULL,
  notes TEXT
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  pet_name VARCHAR(255) NOT NULL,
  service_type VARCHAR(128) NOT NULL,
  app_date DATE NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'Pending'
);

CREATE TABLE IF NOT EXISTS grooming_appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  pet_name VARCHAR(255) NOT NULL,
  service_type VARCHAR(128) NOT NULL,
  app_date DATE NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'Pending'
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(128) NOT NULL,
  item_price DECIMAL(10,2) NOT NULL,
  item_quantity INT NOT NULL DEFAULT 0,
  description VARCHAR(255) DEFAULT NULL,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  UNIQUE KEY uniq_item_name (item_name)
);

CREATE TABLE IF NOT EXISTS wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  item_price DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uniq_wish (username, item_name)
);

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL,
  stripe_session_id VARCHAR(255) NULL,
  items_json TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_stripe_session (stripe_session_id)
);

CREATE TABLE IF NOT EXISTS video_consultations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  pet_name VARCHAR(255) DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  room_name VARCHAR(255) NOT NULL,
  meet_url VARCHAR(512) NOT NULL,
  status VARCHAR(32) DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO items (item_name, category, item_price, item_quantity, description, low_stock_threshold) VALUES
('Adult Dog Food 5kg', 'Food', 24.99, 20, 'Dry kibble for adult dogs', 5),
('Kitten Wet Food Pack', 'Food', 12.50, 15, 'Grain-free wet food pouches', 5),
('Flea Collar', 'Medicine', 9.99, 12, 'Adjustable flea and tick collar', 4),
('Nail Clippers', 'Grooming', 7.49, 18, 'Safety-guard nail clippers', 5),
('Squeaky Toy Bone', 'Toys', 4.99, 25, 'Rubber chew toy', 6),
('Nylon Collar', 'Accessories', 8.99, 3, 'Adjustable nylon collar', 5);
