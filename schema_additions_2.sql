-- =============================================================
-- schema_additions_2.sql
-- Adds tables/columns for the remaining features: 2, 3, 8, 12/13, 16, 18, 19, 20.
-- Run schema_additions.sql FIRST if you haven't already (it creates
-- orders, which `payments` below references).
-- =============================================================

-- Feature 2: Profile & Pet Care Management
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    pet_name VARCHAR(255) NOT NULL,
    species VARCHAR(100),
    breed VARCHAR(100),
    age INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feature 3: Video Consultation
CREATE TABLE IF NOT EXISTS video_consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    pet_name VARCHAR(255),
    session_date DATE NOT NULL,
    session_time VARCHAR(20) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Scheduled',
    room_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feature 8: AI Chatbot conversation log
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,   -- 'user' or 'bot'
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Features 12/13/19/20: turn `items` into a real, manageable catalog.
-- If `items` doesn't exist yet, create it fresh:
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    item_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    item_quantity INT NOT NULL DEFAULT 0,
    description VARCHAR(500) DEFAULT '',
    low_stock_threshold INT NOT NULL DEFAULT 5
);
-- If `items` already existed from earlier work (payment.php referenced it),
-- run whichever of these ALTERs apply instead of the CREATE above,
-- skipping any column that already exists:
-- ALTER TABLE items ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'General';
-- ALTER TABLE items ADD COLUMN item_price DECIMAL(10,2) NOT NULL DEFAULT 0;
-- ALTER TABLE items ADD COLUMN description VARCHAR(500) DEFAULT '';
-- ALTER TABLE items ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5;
-- ALTER TABLE items ADD UNIQUE KEY unique_item_name (item_name);

-- Seed the 6 products that used to be hardcoded in pawmart.php
INSERT INTO items (item_name, category, item_price, item_quantity, description, low_stock_threshold) VALUES
('Dry Dog Food', 'Food', 12.99, 50, 'Nutritious daily kibble for adult dogs.', 10),
('Cat Treats', 'Food', 5.49, 50, 'Crunchy bite-sized treats cats love.', 10),
('Vitamin Supplements', 'Medicine', 9.99, 30, 'Daily multivitamin chews for pets.', 8),
('Worming Tablets', 'Medicine', 7.50, 30, 'Broad-spectrum deworming tablets.', 8),
('Pet Collar', 'Accessories', 6.99, 40, 'Adjustable, durable pet collar.', 10),
('Chew Toys', 'Accessories', 8.25, 40, 'Durable rubber chew toy.', 10)
ON DUPLICATE KEY UPDATE item_name = item_name;

-- Feature 16 / 18: Stripe payments + payment history
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT DEFAULT NULL,
    username VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    stripe_session_id VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Feature 19/20: simple admin flag so admin_inventory.php can restrict access
ALTER TABLE consumer ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user';
-- After running this, promote yourself for testing, e.g.:
-- UPDATE consumer SET role = 'admin' WHERE Email = 'your@email.com';
