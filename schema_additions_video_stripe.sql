-- PawMart additions: video consultations + payment history (Stripe + cash log)
-- Safe to run on an existing kitty-pup-stuffs database.
-- phpMyAdmin: Import this file, or paste into the SQL tab after selecting the database.

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
