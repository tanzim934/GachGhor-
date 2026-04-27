-- ============================================================
-- GachGhor (গাছঘর) — Plants, Trees & Gardening eCommerce
-- Database: gachghor
-- Created for XAMPP / MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS gachghor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gachghor;

-- ============================================================
-- TABLE: users
-- Stores both Admin and Customer accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- bcrypt hashed
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    role ENUM('admin','customer') DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT NULL,
    is_blocked TINYINT(1) DEFAULT 0,         -- 1 = blocked
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: categories
-- Product categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT '🌿',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: products
-- All plant/gardening products
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,   -- discounted price
    stock INT DEFAULT 0,
    description TEXT,
    care_watering VARCHAR(255),              -- e.g. "Every 3 days"
    care_sunlight VARCHAR(255),              -- e.g. "Indirect light"
    care_temperature VARCHAR(100),
    image VARCHAR(255),                      -- primary image filename
    image2 VARCHAR(255),
    image3 VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: cart
-- Shopping cart (per user, per session)
-- ============================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (user_id, product_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: wishlist
-- User saved/favourited products
-- ============================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wish (user_id, product_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: coupons
-- Discount coupon codes
-- ============================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    discount DECIMAL(10,2) NOT NULL,         -- % or BDT amount
    min_order DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT 100,
    used_count INT DEFAULT 0,
    expiry_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: orders
-- Customer orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(30) UNIQUE,         -- e.g. GG-20240101-001
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    shipping DECIMAL(10,2) DEFAULT 60.00,
    total_price DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method ENUM('cod','online') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    shipping_name VARCHAR(100),
    shipping_phone VARCHAR(20),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: order_items
-- Line items for each order
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200),               -- snapshot at time of order
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,            -- price per unit at time of order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: reviews
-- Product reviews and star ratings
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY one_review (product_id, user_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: blog_posts
-- Plant care blog articles
-- ============================================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    content LONGTEXT,
    image VARCHAR(255),
    author_id INT,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: subscriptions
-- Monthly plant delivery subscriptions
-- ============================================================
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan ENUM('basic','standard','premium') DEFAULT 'basic',
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active','paused','cancelled') DEFAULT 'active',
    next_delivery DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: contact_messages
-- Contact form submissions
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin', 'admin@gachghor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000000', 'admin');

-- Demo customers (password: password)
INSERT INTO users (name, email, password, phone, address, city, role) VALUES
('Rahim Uddin', 'rahim@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01811111111', 'House 12, Road 5, Dhanmondi', 'Dhaka', 'customer'),
('Sumaiya Begum', 'sumaiya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01922222222', 'Flat 3B, Gulshan Avenue', 'Dhaka', 'customer'),
('Karim Hossain', 'karim@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01733333333', 'Block C, Bashundhara', 'Dhaka', 'customer');

-- Categories
INSERT INTO categories (name, slug, icon, description) VALUES
('Indoor Plants', 'indoor', '🪴', 'Beautiful plants for indoor spaces'),
('Outdoor Plants', 'outdoor', '🌳', 'Trees and plants for gardens'),
('Bonsai', 'bonsai', '🎋', 'Miniature artistic trees'),
('Tools', 'tools', '🛠️', 'Gardening tools and accessories'),
('Pots & Planters', 'pots', '🏺', 'Decorative pots and planters'),
('Seeds', 'seeds', '🌱', 'Seeds for growing your own plants');

-- Products
INSERT INTO products (name, slug, category_id, price, sale_price, stock, description, care_watering, care_sunlight, care_temperature, image, is_featured) VALUES
('Money Plant (Pothos)', 'money-plant-pothos', 1, 150.00, 120.00, 50, 'The Money Plant is a popular indoor houseplant known for its heart-shaped leaves and air-purifying qualities. Easy to care for and great for beginners.', 'Every 5-7 days', 'Low to bright indirect light', '15-30°C', 'money-plant.jpg', 1),
('Peace Lily', 'peace-lily', 1, 280.00, NULL, 30, 'Peace Lily is an elegant flowering houseplant that thrives in low light. Excellent air purifier and very easy to maintain.', 'Every 3-4 days', 'Low to medium indirect light', '18-27°C', 'peace-lily.jpg', 1),
('Snake Plant', 'snake-plant', 1, 220.00, 199.00, 45, 'Snake Plant (Sansevieria) is one of the toughest houseplants. It tolerates neglect and purifies air by night.', 'Every 7-10 days', 'Any light condition', '15-35°C', 'snake-plant.jpg', 1),
('Fiddle Leaf Fig', 'fiddle-leaf-fig', 1, 850.00, NULL, 15, 'A dramatic statement plant with large, violin-shaped leaves. Perfect for living rooms and offices.', 'Every 7 days', 'Bright indirect light', '18-30°C', 'fiddle-leaf.jpg', 0),
('Mango Tree (Sapling)', 'mango-tree-sapling', 2, 350.00, 299.00, 25, 'Local Himsagar mango variety sapling. Produces sweet fruits after 3-4 years. Great for large gardens.', 'Every 2-3 days', 'Full sun', '20-35°C', 'mango-sapling.jpg', 1),
('Neem Tree', 'neem-tree', 2, 180.00, NULL, 40, 'Neem tree is a fast-growing tree known for its medicinal properties. Excellent for organic gardening.', 'Every 3-4 days', 'Full sun', '20-40°C', 'neem-tree.jpg', 0),
('Banyan Fig (Ficus benghalensis)', 'banyan-fig-bonsai', 3, 1200.00, 999.00, 10, 'Beautiful Banyan Bonsai, approx 8 years old. A living work of art for your home or office.', 'Every 2-3 days', 'Bright indirect to direct light', '18-30°C', 'banyan-bonsai.jpg', 1),
('Juniper Bonsai', 'juniper-bonsai', 3, 950.00, NULL, 8, 'Classic Juniper Bonsai tree. Perfect for outdoor display. Approximately 6 years old.', 'Every 2 days in summer', 'Full sun outdoors', '10-25°C', 'juniper-bonsai.jpg', 0),
('Garden Pruning Shears', 'pruning-shears', 4, 320.00, 280.00, 60, 'Professional stainless steel pruning shears with ergonomic grip. Ideal for trimming plants, flowers and shrubs.', NULL, NULL, NULL, 'pruning-shears.jpg', 0),
('Watering Can (3L)', 'watering-can-3l', 4, 450.00, NULL, 35, 'Stylish metal watering can with long spout. 3 litre capacity. Perfect for indoor and outdoor plants.', NULL, NULL, NULL, 'watering-can.jpg', 0),
('Trowel & Cultivator Set', 'trowel-cultivator-set', 4, 280.00, 249.00, 55, 'Heavy-duty stainless steel trowel and cultivator set with wooden handles. Essential for potting and planting.', NULL, NULL, NULL, 'trowel-set.jpg', 0),
('Ceramic Pot (White, 8 inch)', 'ceramic-pot-white-8', 5, 380.00, NULL, 40, 'Minimalist white glazed ceramic pot with drainage hole and matching saucer. 8 inch diameter.', NULL, NULL, NULL, 'ceramic-pot.jpg', 1),
('Terracotta Pot Set (3 pcs)', 'terracotta-pot-set-3', 5, 250.00, 220.00, 50, 'Set of 3 classic terracotta pots in sizes 4", 6" and 8". Natural and breathable for plant roots.', NULL, NULL, NULL, 'terracotta-set.jpg', 0),
('Hanging Macrame Planter', 'hanging-macrame-planter', 5, 190.00, NULL, 30, 'Handmade boho-style macrame hanging planter. Perfect for trailing plants. Fits up to 6 inch pot.', NULL, NULL, NULL, 'macrame-planter.jpg', 0),
('Basil Seeds (50 pcs)', 'basil-seeds-50', 6, 80.00, NULL, 100, 'Fresh basil seeds for growing aromatic basil at home. Easy to germinate. Ideal for kitchen gardens.', NULL, NULL, NULL, 'basil-seeds.jpg', 0),
('Rose Seeds Mix (25 pcs)', 'rose-seeds-mix', 6, 120.00, 99.00, 80, 'Mixed variety rose seeds including red, pink and white. Germination rate 85%+.', NULL, NULL, NULL, 'rose-seeds.jpg', 0);

-- Coupons
INSERT INTO coupons (code, type, discount, min_order, max_uses, expiry_date) VALUES
('GACHGHOR10', 'percentage', 10.00, 500.00, 200, '2025-12-31'),
('WELCOME50', 'fixed', 50.00, 300.00, 100, '2025-06-30'),
('SALE20', 'percentage', 20.00, 1000.00, 50, '2025-03-31');

-- Sample reviews
INSERT INTO reviews (product_id, user_id, rating, review) VALUES
(1, 2, 5, 'এই গাছটা আমার ঘরে অনেক সুন্দর দেখাচ্ছে। খুব সহজেই যত্ন নেওয়া যায়।'),
(1, 3, 4, 'Great plant for beginners! Grows really fast and looks beautiful.'),
(2, 2, 5, 'Peace Lily is my favourite now. Bloomed within 2 weeks of getting it!'),
(5, 4, 5, 'The mango sapling is healthy and well-packed. Excellent seller!'),
(7, 3, 5, 'The bonsai arrived in perfect condition. It is a masterpiece!');

-- Sample blog posts
INSERT INTO blog_posts (title, slug, content, author_id) VALUES
('5 Best Indoor Plants for Bangladeshi Homes', '5-best-indoor-plants-bangladesh', '<p>Bangladesh\'s tropical climate makes it ideal for growing a wide variety of plants...</p><p>Here are our top 5 picks for indoor plants that thrive in our climate...</p>', 1),
('How to Water Your Plants in Summer', 'how-to-water-plants-summer', '<p>Summer in Bangladesh can be harsh. Here is a guide to keeping your plants hydrated...</p>', 1),
('Beginner\'s Guide to Bonsai in Bangladesh', 'beginners-guide-bonsai', '<p>Bonsai is the art of growing miniature trees. This ancient art form is now growing in popularity in Bangladesh...</p>', 1);

-- Sample order
INSERT INTO orders (user_id, order_number, subtotal, discount, shipping, total_price, coupon_code, payment_method, status, shipping_name, shipping_phone, shipping_address, shipping_city) VALUES
(2, 'GG-2024001', 750.00, 75.00, 60.00, 735.00, 'GACHGHOR10', 'cod', 'delivered', 'Rahim Uddin', '01811111111', 'House 12, Road 5, Dhanmondi', 'Dhaka'),
(3, 'GG-2024002', 1200.00, 0.00, 60.00, 1260.00, NULL, 'cod', 'shipped', 'Sumaiya Begum', '01922222222', 'Flat 3B, Gulshan Avenue', 'Dhaka');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES
(1, 1, 'Money Plant (Pothos)', 2, 120.00),
(1, 12, 'Ceramic Pot (White, 8 inch)', 1, 380.00),
(2, 7, 'Banyan Fig (Ficus benghalensis)', 1, 999.00);
