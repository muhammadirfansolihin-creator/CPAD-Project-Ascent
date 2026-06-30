-- CampusEats Database Schema
-- Run this in MySQL/Laragon before starting the backend.

CREATE DATABASE IF NOT EXISTS campuseats CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campuseats;

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role        ENUM('student','vendor','admin') NOT NULL DEFAULT 'student',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vendors (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    owner_id      INT NOT NULL,
    name          VARCHAR(120) NOT NULL,
    location      VARCHAR(191) NOT NULL,
    opening_hours VARCHAR(100) NOT NULL,
    image_url     VARCHAR(500),
    is_active     TINYINT(1) NOT NULL DEFAULT 0,
    is_open       TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS menu_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id   INT NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    category    ENUM('rice','noodles','drinks','snacks','other') NOT NULL DEFAULT 'other',
    image_url   VARCHAR(500),
    in_stock    TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

CREATE TABLE IF NOT EXISTS orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    vendor_id  INT NOT NULL,
    status     ENUM('placed','preparing','ready','collected') NOT NULL DEFAULT 'placed',
    total      DECIMAL(10,2) NOT NULL,
    pickup_at  VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    menu_item_id INT NOT NULL,
    name         VARCHAR(120) NOT NULL,
    qty          INT NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)     REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    vendor_id  INT NOT NULL,
    order_id   INT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (order_id)  REFERENCES orders(id)
);

CREATE TABLE IF NOT EXISTS disputes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    reported_by INT NOT NULL,
    description TEXT NOT NULL,
    status      ENUM('open','resolved') NOT NULL DEFAULT 'open',
    resolution  TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    order_id   INT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE IF NOT EXISTS dynamic_banners(
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    subtitle VARCHAR(150) NOT NULL,
    theme VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Seed demo accounts (password: password123)
INSERT IGNORE INTO users (name, email, password_hash, role) VALUES
('Administrator',   'admin@campuseats.my',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ahmad Vendor',    'vendor@campuseats.my',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor'),
('Nur Student',     'student@campuseats.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Seed demo vendors
INSERT IGNORE INTO vendors (owner_id, name, location, opening_hours, is_active, is_open, status) VALUES
(2, 'Mak Cik Kantin',       'Block A, Level 1',        '7:30 AM - 5:00 PM', 1, 1, 'active'),
(2, 'Warung Nasi Arif',     'Block B, Food Court',     '8:00 AM - 4:00 PM', 1, 1, 'active'),
(2, 'Kedai Minuman Segar',  'Block C, Ground Floor',   '7:00 AM - 6:00 PM', 1, 0, 'active');

-- Seed demo menu items
INSERT IGNORE INTO menu_items (vendor_id, name, description, price, category, in_stock) VALUES
(1, 'Nasi Lemak Ayam',     'Fragrant coconut rice with crispy fried chicken, sambal, and egg', 7.50, 'rice',    1),
(1, 'Nasi Campur',         'White rice with your choice of side dishes',                        5.00, 'rice',    1),
(1, 'Mee Goreng Mamak',    'Stir-fried yellow noodles with egg, tofu and vegetables',           6.00, 'noodles', 1),
(1, 'Teh Tarik',           'Pulled milk tea, sweet and frothy',                                 2.00, 'drinks',  1),
(1, 'Kuih Lapis',          'Layered steamed rice cake',                                         1.50, 'snacks',  1),
(2, 'Ayam Goreng Berempah','Crispy spiced fried chicken with white rice',                       8.00, 'rice',    1),
(2, 'Nasi Dagang',         'Traditional rice cooked in coconut milk with tuna curry',           7.00, 'rice',    1),
(2, 'Laksa Johor',         'Thick noodles in rich fish-based curry gravy',                      8.50, 'noodles', 1),
(2, 'Bandung Rose',        'Rose syrup with evaporated milk and soda',                          2.50, 'drinks',  1),
(3, 'Sirap Bandung',       'Classic pink rose syrup drink',                                     2.00, 'drinks',  1),
(3, 'Milo Ais',            'Iced chocolate malt drink',                                         2.50, 'drinks',  1),
(3, 'Cendol',              'Shaved ice with coconut milk, palm sugar and green jelly',          3.50, 'snacks',  1),
(3, 'Air Tebu',            'Fresh sugarcane juice',                                             3.00, 'drinks',  1);

INSERT IGNORE INTO dynamic_banners (title, subtitle, theme, start_time, end_time) VALUES
('Breakfast Deal', 'Get 20% off all Nasi Lemak stalls before 10 AM!', 'breakfast', '07:00:00', '11:30:00'),
('Lunch Rush','Free drinks for meals below RM7.50!','lunch','12:30:00','14:30:00'),
('Dinner Dash', 'Satisfy your late-night cravings with RM3 off all dinner sets after 6 PM!','dinner','18:00:00','22:00:00');