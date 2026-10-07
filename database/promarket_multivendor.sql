-- ProMarket Multi-Vendor Marketplace Database
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `promarket_multivendor` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `promarket_multivendor`;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS vendor_withdrawals;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS coupon_usage;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS inventory_log;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','vendor','customer') NOT NULL DEFAULT 'customer',
  phone VARCHAR(40) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE vendors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  business_name VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  owner_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  country VARCHAR(100) DEFAULT 'Ethiopia',
  description TEXT DEFAULT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
  commission_rate DECIMAL(5,2) DEFAULT 10.00,
  total_sales DECIMAL(14,2) DEFAULT 0.00,
  balance DECIMAL(14,2) DEFAULT 0.00,
  approved_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  label VARCHAR(50) DEFAULT 'Home',
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  address_line1 VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  country VARCHAR(100) NOT NULL DEFAULT 'Ethiopia',
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  sku VARCHAR(80) NOT NULL,
  description TEXT DEFAULT NULL,
  specifications TEXT DEFAULT NULL,
  price DECIMAL(12,2) NOT NULL,
  discount_price DECIMAL(12,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT DEFAULT 5,
  is_featured TINYINT(1) DEFAULT 0,
  is_bestseller TINYINT(1) DEFAULT 0,
  status ENUM('draft','pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
  views INT DEFAULT 0,
  sales_count INT DEFAULT 0,
  avg_rating DECIMAL(3,2) DEFAULT 0.00,
  review_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  UNIQUE KEY uk_vendor_sku (vendor_id, sku),
  INDEX idx_status (status),
  INDEX idx_featured (is_featured),
  FULLTEXT INDEX ft_search (name, description, sku)
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(30) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_email VARCHAR(160) NOT NULL,
  customer_phone VARCHAR(40) NOT NULL,
  shipping_address TEXT NOT NULL,
  shipping_city VARCHAR(100) NOT NULL,
  shipping_country VARCHAR(100) NOT NULL DEFAULT 'Ethiopia',
  subtotal DECIMAL(12,2) NOT NULL,
  discount_amount DECIMAL(12,2) DEFAULT 0.00,
  shipping_amount DECIMAL(12,2) DEFAULT 0.00,
  tax_amount DECIMAL(12,2) DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL,
  coupon_code VARCHAR(50) DEFAULT NULL,
  payment_method ENUM('cod','bank_transfer','card','mobile_money') NOT NULL DEFAULT 'cod',
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  vendor_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(200) NOT NULL,
  product_sku VARCHAR(80) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE cart (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uk_user_product (user_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE wishlist (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uk_user_product (user_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  rating TINYINT NOT NULL,
  title VARCHAR(200) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(12,2) NOT NULL,
  min_order_amount DECIMAL(12,2) DEFAULT 0.00,
  max_discount DECIMAL(12,2) DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  used_count INT DEFAULT 0,
  start_date DATE NOT NULL,
  expiry_date DATE NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  transaction_id VARCHAR(100) DEFAULT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inventory_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  vendor_id INT UNSIGNED NOT NULL,
  change_type ENUM('sale','restock','adjustment','return') NOT NULL,
  quantity_change INT NOT NULL,
  quantity_before INT NOT NULL,
  quantity_after INT NOT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  reference_id INT DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vendor_withdrawals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
  account_details TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT DEFAULT NULL,
  setting_group VARCHAR(50) DEFAULT 'general'
) ENGINE=InnoDB;

-- DEMO DATA (password for all: Admin123!)
INSERT INTO users (id, name, email, password, role, phone, status) VALUES
(1, 'System Admin', 'admin@promarket.local', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'admin', '+251911000001', 'active'),
(2, 'Abebe Kebede', 'abebe@techhub.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100001', 'active'),
(3, 'Sara Mohammed', 'sara@fashionet.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100002', 'active'),
(4, 'Daniel Haile', 'daniel@homeplus.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100003', 'active'),
(5, 'Hanna Tadesse', 'hanna@beautybox.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100004', 'active'),
(6, 'Yonas Girma', 'yonas@sportzone.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100005', 'active'),
(7, 'Mekdes Alemu', 'mekdes@bookworld.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100006', 'active'),
(8, 'Kidus Bekele', 'kidus@gadgetpro.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100007', 'active'),
(9, 'Ruth Assefa', 'ruth@organicfarm.et', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'vendor', '+251911100008', 'active'),
(10, 'Selamawit Bekele', 'customer1@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000001', 'active'),
(11, 'Tewodros Alemu', 'customer2@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000002', 'active'),
(12, 'Meron Haile', 'customer3@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000003', 'active'),
(13, 'Getachew Tadesse', 'customer4@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000004', 'active'),
(14, 'Betty Girma', 'customer5@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000005', 'active'),
(15, 'Fikadu Mohammed', 'customer6@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000006', 'active'),
(16, 'Hiwot Assefa', 'customer7@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000007', 'active'),
(17, 'Solomon Kebede', 'customer8@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000008', 'active'),
(18, 'Rahel Desta', 'customer9@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000009', 'active'),
(19, 'Mulugeta Yohannes', 'customer10@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000010', 'active'),
(20, 'Eyerusalem Tadesse', 'customer11@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000011', 'active'),
(21, 'Dawit Mekonnen', 'customer12@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000012', 'active'),
(22, 'Tsion Abebe', 'customer13@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000013', 'active'),
(23, 'Yared Hailemariam', 'customer14@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000014', 'active'),
(24, 'Liya Solomon', 'customer15@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000015', 'active'),
(25, 'Bereket Tekle', 'customer16@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000016', 'active'),
(26, 'Netsanet Wolde', 'customer17@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000017', 'active'),
(27, 'Abel Getachew', 'customer18@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000018', 'active'),
(28, 'Mihret Asfaw', 'customer19@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000019', 'active'),
(29, 'Kirubel Demissie', 'customer20@email.com', '$2y$10$FE9kU6roKzKwTr3OJjmSMOoiGOMcskJJMNjdizj9JmQM2YuW9/Aja', 'customer', '+251912000020', 'active');

INSERT INTO vendors (id, user_id, business_name, slug, owner_name, email, phone, address, city, description, status, commission_rate, total_sales, balance, approved_at) VALUES
(1, 2, 'TechHub Electronics', 'techhub-electronics', 'Abebe Kebede', 'abebe@techhub.et', '+251911100001', 'Bole Road', 'Addis Ababa', 'Premium electronics and gadgets.', 'approved', 10.00, 125000.00, 45000.00, NOW()),
(2, 3, 'FashionET Boutique', 'fashionet-boutique', 'Sara Mohammed', 'sara@fashionet.et', '+251911100002', 'Piassa', 'Addis Ababa', 'Trendy fashion for everyone.', 'approved', 12.00, 89000.00, 32000.00, NOW()),
(3, 4, 'HomePlus Living', 'homeplus-living', 'Daniel Haile', 'daniel@homeplus.et', '+251911100003', 'Mexico Square', 'Addis Ababa', 'Furniture and home essentials.', 'approved', 10.00, 67000.00, 21000.00, NOW()),
(4, 5, 'BeautyBox Cosmetics', 'beautybox-cosmetics', 'Hanna Tadesse', 'hanna@beautybox.et', '+251911100004', 'Kazanchis', 'Addis Ababa', 'Skincare and beauty products.', 'approved', 15.00, 54000.00, 18000.00, NOW()),
(5, 6, 'SportZone Ethiopia', 'sportzone-ethiopia', 'Yonas Girma', 'yonas@sportzone.et', '+251911100005', 'Sarbet', 'Addis Ababa', 'Sports and fitness gear.', 'approved', 10.00, 43000.00, 15000.00, NOW()),
(6, 7, 'BookWorld Ethiopia', 'bookworld-ethiopia', 'Mekdes Alemu', 'mekdes@bookworld.et', '+251911100006', 'Arat Kilo', 'Addis Ababa', 'Books and stationery.', 'approved', 8.00, 28000.00, 9000.00, NOW()),
(7, 8, 'GadgetPro Store', 'gadgetpro-store', 'Kidus Bekele', 'kidus@gadgetpro.et', '+251911100007', 'CMC', 'Addis Ababa', 'Latest gadgets and accessories.', 'approved', 10.00, 76000.00, 28000.00, NOW()),
(8, 9, 'OrganicFarm Market', 'organicfarm-market', 'Ruth Assefa', 'ruth@organicfarm.et', '+251911100008', 'Lebu', 'Addis Ababa', 'Organic foods and spices.', 'approved', 8.00, 35000.00, 12000.00, NOW());

INSERT INTO customers (id, user_id, full_name, phone) VALUES
(1,10,'Selamawit Bekele','+251912000001'),(2,11,'Tewodros Alemu','+251912000002'),(3,12,'Meron Haile','+251912000003'),
(4,13,'Getachew Tadesse','+251912000004'),(5,14,'Betty Girma','+251912000005'),(6,15,'Fikadu Mohammed','+251912000006'),
(7,16,'Hiwot Assefa','+251912000007'),(8,17,'Solomon Kebede','+251912000008'),(9,18,'Rahel Desta','+251912000009'),
(10,19,'Mulugeta Yohannes','+251912000010'),(11,20,'Eyerusalem Tadesse','+251912000011'),(12,21,'Dawit Mekonnen','+251912000012'),
(13,22,'Tsion Abebe','+251912000013'),(14,23,'Yared Hailemariam','+251912000014'),(15,24,'Liya Solomon','+251912000015'),
(16,25,'Bereket Tekle','+251912000016'),(17,26,'Netsanet Wolde','+251912000017'),(18,27,'Abel Getachew','+251912000018'),
(19,28,'Mihret Asfaw','+251912000019'),(20,29,'Kirubel Demissie','+251912000020');

INSERT INTO categories (id, name, slug, description, status, sort_order) VALUES
(1,'Electronics','electronics','Smartphones, laptops and gadgets','active',1),
(2,'Fashion','fashion','Clothing and accessories','active',2),
(3,'Home & Living','home-living','Furniture and home decor','active',3),
(4,'Beauty & Personal Care','beauty-personal-care','Skincare and cosmetics','active',4),
(5,'Sports & Outdoors','sports-outdoors','Sports equipment and fitness','active',5),
(6,'Books & Stationery','books-stationery','Books and office supplies','active',6),
(7,'Food & Grocery','food-grocery','Organic foods and spices','active',7),
(8,'Phones & Accessories','phones-accessories','Mobile phones and accessories','active',8),
(9,'Computers','computers','Laptops and computer gear','active',9),
(10,'Health & Wellness','health-wellness','Health and wellness products','active',10);

INSERT INTO products (id, vendor_id, category_id, name, slug, sku, description, specifications, price, discount_price, stock, is_featured, is_bestseller, status, views, sales_count, avg_rating, review_count) VALUES
(1,1,1,'Samsung Galaxy A54 5G','samsung-galaxy-a54-5g','TH-SG-A54','Powerful mid-range smartphone with 50MP camera and 120Hz display.','Display: 6.4 Super AMOLED\nRAM: 8GB\nStorage: 128GB\nBattery: 5000mAh',32999,29999,45,1,1,'active',1250,28,4.50,12),
(2,1,9,'HP Pavilion 15 Laptop','hp-pavilion-15-laptop','TH-HP-P15','Reliable laptop with Intel Core i5, 16GB RAM and 512GB SSD.','CPU: Intel i5-1235U\nRAM: 16GB\nStorage: 512GB SSD\nDisplay: 15.6 FHD',54999,49999,22,1,1,'active',890,15,4.30,8),
(3,1,1,'Sony WH-1000XM5 Headphones','sony-wh-1000xm5','TH-SN-XM5','Industry-leading noise cancelling wireless headphones.','Type: Over-ear\nANC: Yes\nBattery: 30 hours',24999,NULL,18,1,0,'active',670,9,4.80,6),
(4,7,8,'iPhone 14 128GB','iphone-14-128gb','GP-IP14-128','Apple iPhone 14 with A15 Bionic chip and dual camera.','Display: 6.1 Super Retina\nChip: A15\nStorage: 128GB',69999,64999,12,1,1,'active',2100,22,4.70,15),
(5,7,1,'Anker PowerBank 20000mAh','anker-powerbank-20000','GP-AK-PB20','High-capacity portable charger with fast charging.','Capacity: 20000mAh\nOutput: 22.5W',3499,2999,85,0,1,'active',540,45,4.40,20),
(6,2,2,'Mens Casual Oxford Shirt','mens-casual-oxford-shirt','FE-MCS-001','Premium cotton oxford shirt for men.','Material: 100% Cotton\nFit: Regular\nSizes: S-XXL',1899,1499,60,1,0,'active',320,18,4.20,7),
(7,2,2,'Womens Summer Maxi Dress','womens-summer-maxi-dress','FE-WMD-002','Elegant floral maxi dress for summer.','Material: Polyester Blend\nSizes: S-XL',2499,1999,35,1,1,'active',780,25,4.60,11),
(8,2,2,'Unisex Running Sneakers','unisex-running-sneakers','FE-URS-003','Comfortable lightweight running shoes.','Material: Mesh + Rubber\nSizes: 36-45',3299,NULL,40,0,0,'active',410,12,4.10,5),
(9,3,3,'Modern Wooden Coffee Table','modern-wooden-coffee-table','HP-WCT-001','Stylish solid wood coffee table with storage.','Material: Solid Wood\nSize: 100x50x45cm',8999,7499,15,1,0,'active',290,8,4.50,4),
(10,3,3,'Non-Stick Cookware Set 10pcs','nonstick-cookware-set','HP-NCS-002','Complete non-stick cookware set.','Pieces: 10\nCompatible: Gas, Electric, Induction',4599,3999,28,0,1,'active',560,19,4.30,9),
(11,3,3,'LED Desk Lamp with USB','led-desk-lamp-usb','HP-LDL-003','Adjustable LED desk lamp with USB port.','Power: 10W\nBrightness: 3 Levels',1299,NULL,50,0,0,'active',180,14,4.00,3),
(12,4,4,'Vitamin C Face Serum 30ml','vitamin-c-face-serum','BB-VCS-001','Brightening vitamin C serum for glowing skin.','Volume: 30ml\nKey: 20% Vitamin C',899,699,100,1,1,'active',920,55,4.70,28),
(13,4,4,'Matte Lipstick Set 6 Colors','matte-lipstick-set','BB-MLS-002','Long-lasting matte lipstick set.','Shades: 6\nFinish: Matte',1499,1199,70,0,1,'active',650,32,4.50,16),
(14,4,4,'Hair Growth Oil 100ml','hair-growth-oil','BB-HGO-003','Natural hair growth oil with rosemary and castor.','Volume: 100ml\nNatural: 100%',799,NULL,90,0,0,'active',430,21,4.20,10),
(15,5,5,'Yoga Mat Premium 6mm','yoga-mat-premium','SZ-YMP-001','Extra thick non-slip yoga mat.','Thickness: 6mm\nSize: 183x61cm',1899,1499,55,1,0,'active',380,16,4.40,7),
(16,5,5,'Adjustable Dumbbell Set 20kg','adjustable-dumbbell-set','SZ-ADS-002','Space-saving adjustable dumbbells.','Weight: 2.5-20kg\nMaterial: Cast Iron',5999,4999,20,1,1,'active',720,11,4.60,6),
(17,5,5,'Sports Water Bottle 1L','sports-water-bottle','SZ-SWB-003','BPA-free sports water bottle with time markers.','Capacity: 1L\nBPA-Free: Yes',499,399,120,0,1,'active',290,48,4.10,14),
(18,6,6,'Atomic Habits - James Clear','atomic-habits','BW-AH-001','Bestseller on building good habits.','Author: James Clear\nPages: 320',899,749,80,1,1,'active',1100,62,4.90,35),
(19,6,6,'Amharic Dictionary Hardcover','amharic-dictionary','BW-AD-002','Comprehensive Amharic-English dictionary.','Pages: 850\nFormat: Hardcover',1299,NULL,40,0,0,'active',210,9,4.30,4),
(20,6,6,'Premium Notebook Set A5','premium-notebook-set','BW-PNS-003','Set of 3 premium A5 notebooks.','Size: A5\nPages: 120 each',599,499,95,0,0,'active',340,27,4.20,8),
(21,8,7,'Ethiopian Wild Honey 500g','ethiopian-wild-honey','OF-EWH-001','Pure raw Ethiopian wild honey.','Weight: 500g\nOrganic: Yes',699,599,150,1,1,'active',680,78,4.80,42),
(22,8,7,'Organic Berbere Spice 250g','organic-berbere-spice','OF-OBS-002','Authentic Ethiopian berbere spice blend.','Weight: 250g\nOrganic: Certified',349,NULL,200,0,1,'active',450,65,4.60,22),
(23,8,7,'Tej Honey Wine 750ml','tej-honey-wine','OF-THW-003','Traditional Ethiopian honey wine.','Volume: 750ml\nAlcohol: ~8%',899,749,60,0,0,'active',320,18,4.40,9),
(24,1,8,'USB-C Fast Charger 65W','usb-c-fast-charger','TH-UC-065','GaN technology 65W USB-C charger.','Power: 65W\nPorts: 2x USB-C',2499,1999,75,0,1,'active',510,38,4.50,17),
(25,7,1,'Wireless Earbuds Pro','wireless-earbuds-pro','GP-WEP-001','True wireless earbuds with ANC.','ANC: Yes\nBattery: 30hrs\nIPX5',4499,3799,48,1,1,'active',980,34,4.40,19),
(26,2,2,'Leather Crossbody Bag','leather-crossbody-bag','FE-LCB-004','Genuine leather crossbody bag for women.','Material: Genuine Leather\nColor: Brown/Black',3999,3299,25,0,0,'active',270,7,4.30,3),
(27,3,3,'Ceramic Dinner Set 16pcs','ceramic-dinner-set','HP-CDS-004','Elegant white ceramic dinner set for 4.','Pieces: 16\nMicrowave Safe: Yes',3499,2799,30,0,0,'active',190,10,4.10,5),
(28,5,5,'Resistance Bands Set 5pcs','resistance-bands-set','SZ-RBS-004','Complete resistance bands set with 5 levels.','Levels: 5\nMax: 50kg',999,799,80,0,0,'active',350,22,4.20,8),
(29,4,4,'Sunscreen SPF50 50ml','sunscreen-spf50','BB-SS-004','Broad spectrum sunscreen SPF50.','SPF: 50\nVolume: 50ml',649,549,110,0,1,'active',580,41,4.50,18),
(30,6,6,'Kids Storybook Collection','kids-storybook-collection','BW-KSC-004','Set of 5 illustrated storybooks for kids.','Books: 5\nAge: 4-8 years',1299,999,45,0,0,'active',240,13,4.60,6);


INSERT INTO orders (id, order_number, customer_id, customer_name, customer_email, customer_phone, shipping_address, shipping_city, subtotal, discount_amount, shipping_amount, total, payment_method, payment_status, status, created_at) VALUES
(1,'PM-20260315-0001',1,'Selamawit Bekele','customer1@email.com','+251912000001','Bole Medhanialem House 45','Addis Ababa',29999,0,150,30149,'cod','pending','pending','2026-03-15 10:22:00'),
(2,'PM-20260316-0002',2,'Tewodros Alemu','customer2@email.com','+251912000002','Kazanchis Near UNECA','Addis Ababa',49999,0,200,50199,'bank_transfer','paid','confirmed','2026-03-16 14:05:00'),
(3,'PM-20260317-0003',3,'Meron Haile','customer3@email.com','+251912000003','CMC Michael Apt 12B','Addis Ababa',64999,5000,200,60199,'cod','pending','processing','2026-03-17 09:30:00'),
(4,'PM-20260318-0004',4,'Getachew Tadesse','customer4@email.com','+251912000004','Mexico Square Office 301','Addis Ababa',4498,0,100,4598,'cod','paid','shipped','2026-03-18 11:45:00'),
(5,'PM-20260319-0005',5,'Betty Girma','customer5@email.com','+251912000005','Sarbet Villa 7','Addis Ababa',1999,0,80,2079,'mobile_money','paid','delivered','2026-03-19 16:20:00'),
(6,'PM-20260320-0006',6,'Fikadu Mohammed','customer6@email.com','+251912000006','Piassa Building 3','Addis Ababa',7499,0,150,7649,'cod','pending','pending','2026-03-20 08:15:00'),
(7,'PM-20260321-0007',7,'Hiwot Assefa','customer7@email.com','+251912000007','Arat Kilo House 22','Addis Ababa',1499,0,80,1579,'cod','paid','delivered','2026-03-21 13:00:00'),
(8,'PM-20260322-0008',8,'Solomon Kebede','customer8@email.com','+251912000008','Lebu Compound A','Addis Ababa',3799,0,100,3899,'bank_transfer','paid','confirmed','2026-03-22 10:40:00'),
(9,'PM-20260323-0009',9,'Rahel Desta','customer9@email.com','+251912000009','Bole Atlas Apt 5','Addis Ababa',699,0,50,749,'cod','paid','delivered','2026-03-23 15:30:00'),
(10,'PM-20260324-0010',10,'Mulugeta Yohannes','customer10@email.com','+251912000010','Gerji House 88','Addis Ababa',4999,0,150,5149,'cod','pending','processing','2026-03-24 09:10:00'),
(11,'PM-20260325-0011',1,'Selamawit Bekele','customer1@email.com','+251912000001','Bole Medhanialem House 45','Addis Ababa',749,0,50,799,'cod','paid','delivered','2026-03-25 11:00:00'),
(12,'PM-20260326-0012',11,'Eyerusalem Tadesse','customer11@email.com','+251912000011','Megenagna Road 5','Addis Ababa',1499,0,80,1579,'mobile_money','paid','shipped','2026-03-26 14:25:00'),
(13,'PM-20260327-0013',12,'Dawit Mekonnen','customer12@email.com','+251912000012','CMC Block C','Addis Ababa',2999,0,100,3099,'cod','pending','pending','2026-03-27 08:50:00'),
(14,'PM-20260328-0014',13,'Tsion Abebe','customer13@email.com','+251912000013','Hayahulet Apt 9','Addis Ababa',1199,0,80,1279,'cod','paid','delivered','2026-03-28 16:40:00'),
(15,'PM-20260329-0015',14,'Yared Hailemariam','customer14@email.com','+251912000014','Lideta House 15','Addis Ababa',1999,0,100,2099,'bank_transfer','paid','confirmed','2026-03-29 10:15:00'),
(16,'PM-20260401-0016',15,'Liya Solomon','customer15@email.com','+251912000015','Old Airport Villa 3','Addis Ababa',3999,0,120,4119,'cod','pending','pending','2026-04-01 09:00:00'),
(17,'PM-20260402-0017',16,'Bereket Tekle','customer16@email.com','+251912000016','Summit House 42','Addis Ababa',549,0,50,599,'cod','paid','delivered','2026-04-02 12:30:00'),
(18,'PM-20260403-0018',17,'Netsanet Wolde','customer17@email.com','+251912000017','Kality Compound 2','Addis Ababa',3299,0,100,3399,'mobile_money','paid','processing','2026-04-03 15:00:00'),
(19,'PM-20260404-0019',18,'Abel Getachew','customer18@email.com','+251912000018','Jemo Block A-12','Addis Ababa',2799,0,100,2899,'cod','pending','confirmed','2026-04-04 11:20:00'),
(20,'PM-20260405-0020',19,'Mihret Asfaw','customer19@email.com','+251912000019','Ayat House 67','Addis Ababa',999,0,60,1059,'cod','paid','shipped','2026-04-05 13:45:00'),
(21,'PM-20260406-0021',20,'Kirubel Demissie','customer20@email.com','+251912000020','Goro Road 3','Addis Ababa',799,0,50,849,'cod','paid','delivered','2026-04-06 10:00:00'),
(22,'PM-20260407-0022',2,'Tewodros Alemu','customer2@email.com','+251912000002','Kazanchis Near UNECA','Addis Ababa',24999,0,150,25149,'bank_transfer','paid','confirmed','2026-04-07 14:10:00'),
(23,'PM-20260408-0023',5,'Betty Girma','customer5@email.com','+251912000005','Sarbet Villa 7','Addis Ababa',1499,0,80,1579,'cod','pending','pending','2026-04-08 09:30:00'),
(24,'PM-20260409-0024',8,'Solomon Kebede','customer8@email.com','+251912000008','Lebu Compound A','Addis Ababa',599,0,50,649,'cod','paid','delivered','2026-04-09 16:00:00'),
(25,'PM-20260410-0025',3,'Meron Haile','customer3@email.com','+251912000003','CMC Michael Apt 12B','Addis Ababa',4999,500,150,4649,'cod','pending','processing','2026-04-10 11:15:00'),
(26,'PM-20260411-0026',10,'Mulugeta Yohannes','customer10@email.com','+251912000010','Gerji House 88','Addis Ababa',349,0,40,389,'mobile_money','paid','delivered','2026-04-11 08:45:00'),
(27,'PM-20260412-0027',15,'Liya Solomon','customer15@email.com','+251912000015','Old Airport Villa 3','Addis Ababa',3799,0,100,3899,'cod','pending','confirmed','2026-04-12 13:20:00'),
(28,'PM-20260413-0028',7,'Hiwot Assefa','customer7@email.com','+251912000007','Arat Kilo House 22','Addis Ababa',899,0,50,949,'cod','paid','shipped','2026-04-13 10:50:00'),
(29,'PM-20260414-0029',12,'Dawit Mekonnen','customer12@email.com','+251912000012','CMC Block C','Addis Ababa',1999,0,80,2079,'bank_transfer','paid','processing','2026-04-14 15:30:00'),
(30,'PM-20260415-0030',1,'Selamawit Bekele','customer1@email.com','+251912000001','Bole Medhanialem House 45','Addis Ababa',54999,0,250,55249,'cod','pending','pending','2026-04-15 09:00:00'),
(31,'PM-20260416-0031',4,'Getachew Tadesse','customer4@email.com','+251912000004','Mexico Square Office 301','Addis Ababa',4098,0,120,4218,'cod','paid','confirmed','2026-04-16 12:00:00'),
(32,'PM-20260417-0032',9,'Rahel Desta','customer9@email.com','+251912000009','Bole Atlas Apt 5','Addis Ababa',749,0,50,799,'cod','paid','delivered','2026-04-17 14:40:00');

INSERT INTO order_items (order_id, product_id, vendor_id, product_name, product_sku, quantity, unit_price, total_price, status) VALUES
(1,1,1,'Samsung Galaxy A54 5G','TH-SG-A54',1,29999,29999,'pending'),
(2,2,1,'HP Pavilion 15 Laptop','TH-HP-P15',1,49999,49999,'confirmed'),
(3,4,7,'iPhone 14 128GB','GP-IP14-128',1,64999,64999,'processing'),
(4,6,2,'Mens Casual Oxford Shirt','FE-MCS-001',2,1499,2998,'shipped'),
(4,12,4,'Vitamin C Face Serum 30ml','BB-VCS-001',2,699,1398,'shipped'),
(5,7,2,'Womens Summer Maxi Dress','FE-WMD-002',1,1999,1999,'delivered'),
(6,9,3,'Modern Wooden Coffee Table','HP-WCT-001',1,7499,7499,'pending'),
(7,15,5,'Yoga Mat Premium 6mm','SZ-YMP-001',1,1499,1499,'delivered'),
(8,25,7,'Wireless Earbuds Pro','GP-WEP-001',1,3799,3799,'confirmed'),
(9,21,8,'Ethiopian Wild Honey 500g','OF-EWH-001',1,599,599,'delivered'),
(10,16,5,'Adjustable Dumbbell Set 20kg','SZ-ADS-002',1,4999,4999,'processing'),
(11,18,6,'Atomic Habits - James Clear','BW-AH-001',1,749,749,'delivered'),
(12,15,5,'Yoga Mat Premium 6mm','SZ-YMP-001',1,1499,1499,'shipped'),
(13,5,7,'Anker PowerBank 20000mAh','GP-AK-PB20',1,2999,2999,'pending'),
(14,13,4,'Matte Lipstick Set 6 Colors','BB-MLS-002',1,1199,1199,'delivered'),
(15,24,1,'USB-C Fast Charger 65W','TH-UC-065',1,1999,1999,'confirmed'),
(16,10,3,'Non-Stick Cookware Set 10pcs','HP-NCS-002',1,3999,3999,'pending'),
(17,29,4,'Sunscreen SPF50 50ml','BB-SS-004',1,549,549,'delivered'),
(18,8,2,'Unisex Running Sneakers','FE-URS-003',1,3299,3299,'processing'),
(19,27,3,'Ceramic Dinner Set 16pcs','HP-CDS-004',1,2799,2799,'confirmed'),
(20,28,5,'Resistance Bands Set 5pcs','SZ-RBS-004',1,799,799,'shipped'),
(21,22,8,'Organic Berbere Spice 250g','OF-OBS-002',1,349,349,'delivered'),
(22,3,1,'Sony WH-1000XM5 Headphones','TH-SN-XM5',1,24999,24999,'confirmed'),
(23,6,2,'Mens Casual Oxford Shirt','FE-MCS-001',1,1499,1499,'pending'),
(24,21,8,'Ethiopian Wild Honey 500g','OF-EWH-001',1,599,599,'delivered'),
(25,16,5,'Adjustable Dumbbell Set 20kg','SZ-ADS-002',1,4999,4999,'processing'),
(26,22,8,'Organic Berbere Spice 250g','OF-OBS-002',1,349,349,'delivered'),
(27,25,7,'Wireless Earbuds Pro','GP-WEP-001',1,3799,3799,'confirmed'),
(28,12,4,'Vitamin C Face Serum 30ml','BB-VCS-001',1,699,699,'shipped'),
(29,7,2,'Womens Summer Maxi Dress','FE-WMD-002',1,1999,1999,'processing'),
(30,2,1,'HP Pavilion 15 Laptop','TH-HP-P15',1,54999,54999,'pending'),
(31,26,2,'Leather Crossbody Bag','FE-LCB-004',1,3299,3299,'confirmed'),
(31,14,4,'Hair Growth Oil 100ml','BB-HGO-003',1,799,799,'confirmed'),
(32,18,6,'Atomic Habits - James Clear','BW-AH-001',1,749,749,'delivered');

INSERT INTO reviews (product_id, customer_id, rating, title, comment, status) VALUES
(1,1,5,'Excellent phone!','Great display and battery life.','approved'),
(1,5,4,'Good value','Solid mid-range phone.','approved'),
(2,2,4,'Reliable laptop','Perfect for work and study.','approved'),
(4,3,5,'Love this iPhone','Smooth performance and excellent camera.','approved'),
(7,5,5,'Beautiful dress','Fabric is soft and the fit is perfect.','approved'),
(12,4,5,'Glowing skin!','Noticed brighter skin within 2 weeks.','approved'),
(15,7,4,'Great yoga mat','Thick and non-slip.','approved'),
(18,1,5,'Life-changing book','One of the best books on habits.','approved'),
(21,9,5,'Pure honey!','Authentic Ethiopian honey.','approved'),
(25,8,4,'Good earbuds','ANC works well and battery lasts long.','approved'),
(5,12,4,'Powerful bank','Charges my phone multiple times.','approved'),
(16,10,5,'Home gym essential','Easy to adjust weights.','approved'),
(13,13,5,'Nice colors','Long-lasting and comfortable.','approved'),
(22,20,5,'Authentic berbere','Perfect spice blend.','approved'),
(29,16,4,'Good sunscreen','Does not leave white cast.','approved');

INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, used_count, start_date, expiry_date, status) VALUES
('WELCOME10','Welcome 10% off','percentage',10,500,2000,1000,15,'2026-01-01','2026-12-31','active'),
('SAVE500','Flat 500 ETB off','fixed',500,5000,NULL,500,8,'2026-01-01','2026-12-31','active'),
('FASHION15','15% off fashion','percentage',15,1000,3000,200,5,'2026-03-01','2026-06-30','active'),
('TECH20','20% off electronics','percentage',20,10000,10000,100,3,'2026-02-01','2026-08-31','active'),
('FREESHIP','Free shipping','fixed',150,1000,150,300,12,'2026-01-01','2026-12-31','active');

INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('marketplace_name','ProMarket','general'),
('marketplace_tagline','Your Trusted Multi-Vendor Marketplace','general'),
('marketplace_email','support@promarket.local','general'),
('marketplace_phone','+251 11 123 4567','general'),
('marketplace_address','Bole Road, Addis Ababa, Ethiopia','general'),
('currency','ETB','general'),
('currency_symbol','ETB','general'),
('shipping_flat_rate','150','shipping'),
('shipping_free_threshold','5000','shipping'),
('tax_rate','0','tax'),
('low_stock_threshold','5','inventory'),
('order_prefix','PM','orders'),
('items_per_page','12','display'),
('vendor_commission_default','10','vendor');

INSERT INTO addresses (user_id, label, full_name, phone, address_line1, city, country, is_default) VALUES
(10,'Home','Selamawit Bekele','+251912000001','Bole Medhanialem House 45','Addis Ababa','Ethiopia',1),
(11,'Home','Tewodros Alemu','+251912000002','Kazanchis Near UNECA','Addis Ababa','Ethiopia',1),
(12,'Home','Meron Haile','+251912000003','CMC Michael Apt 12B','Addis Ababa','Ethiopia',1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Add 70 more products (IDs 31–100)
-- =====================================================

INSERT INTO products (id, vendor_id, category_id, name, slug, sku, description, specifications, price, discount_price, stock, is_featured, is_bestseller, status, views, sales_count, avg_rating, review_count) VALUES
-- Electronics / Phones / Computers (vendor 1 TechHub, 7 GadgetPro)
(31, 1, 1, 'Samsung Galaxy S23 Ultra', 'samsung-galaxy-s23-ultra', 'TH-SG-S23U', 'Flagship smartphone with 200MP camera and S Pen support.', 'Display: 6.8" Dynamic AMOLED\nRAM: 12GB\nStorage: 256GB\nBattery: 5000mAh', 89999.00, 84999.00, 15, 1, 1, 'active', 2100, 18, 4.80, 9),
(32, 1, 1, 'Google Pixel 7 Pro', 'google-pixel-7-pro', 'TH-GP-P7P', 'Pure Android experience with exceptional computational photography.', 'Display: 6.7" LTPO OLED\nRAM: 12GB\nStorage: 128GB\nCamera: 50MP', 64999.00, NULL, 20, 1, 0, 'active', 980, 7, 4.60, 4),
(33, 1, 9, 'Dell XPS 13 Laptop', 'dell-xps-13-laptop', 'TH-DL-XPS13', 'Ultra-portable premium laptop with InfinityEdge display.', 'CPU: Intel Core i7\nRAM: 16GB\nStorage: 512GB SSD\nDisplay: 13.4" FHD+', 79999.00, 74999.00, 10, 1, 1, 'active', 1500, 12, 4.70, 6),
(34, 1, 9, 'Lenovo ThinkPad E14', 'lenovo-thinkpad-e14', 'TH-LN-E14', 'Reliable business laptop with legendary ThinkPad keyboard.', 'CPU: Intel Core i5\nRAM: 8GB\nStorage: 256GB SSD\nDisplay: 14" FHD', 42999.00, 39999.00, 25, 0, 0, 'active', 620, 9, 4.40, 5),
(35, 1, 1, 'JBL Flip 6 Bluetooth Speaker', 'jbl-flip-6-speaker', 'TH-JBL-F6', 'Portable waterproof speaker with powerful JBL Original Pro Sound.', 'Power: 30W\nBattery: 12 hours\nWaterproof: IP67\nBluetooth: 5.1', 5999.00, 4999.00, 40, 0, 1, 'active', 890, 28, 4.50, 14),
(36, 7, 8, 'iPhone 15 128GB', 'iphone-15-128gb', 'GP-IP15-128', 'Latest iPhone with USB-C, Dynamic Island and advanced camera.', 'Display: 6.1" Super Retina\nChip: A16 Bionic\nStorage: 128GB\nCamera: 48MP', 84999.00, 79999.00, 18, 1, 1, 'active', 3200, 25, 4.90, 16),
(37, 7, 8, 'AirPods Pro 2nd Gen', 'airpods-pro-2', 'GP-APP-2', 'Active Noise Cancellation with Adaptive Transparency.', 'ANC: Yes\nBattery: 6hrs + case 30hrs\nChip: H2\nWater: IPX4', 14999.00, 12999.00, 35, 1, 1, 'active', 1800, 40, 4.70, 22),
(38, 7, 1, 'Samsung 4K Smart TV 55"', 'samsung-4k-tv-55', 'GP-SM-TV55', 'Crystal UHD 4K Smart TV with Tizen OS and HDR.', 'Size: 55"\nResolution: 4K UHD\nHDR: Yes\nSmart: Tizen', 45999.00, 41999.00, 8, 1, 0, 'active', 1100, 6, 4.50, 3),
(39, 7, 1, 'Logitech MX Master 3S Mouse', 'logitech-mx-master-3s', 'GP-LG-MX3S', 'Premium wireless mouse with MagSpeed scroll and quiet clicks.', 'DPI: 8000\nBattery: 70 days\nConnectivity: Bluetooth + USB', 5999.00, NULL, 50, 0, 0, 'active', 450, 15, 4.60, 8),
(40, 7, 8, 'Spigen Tough Armor Case', 'spigen-tough-armor-case', 'GP-SP-TAC', 'Military-grade protection case with kickstand.', 'Material: TPU + PC\nDrop Protection: Military grade\nKickstand: Yes', 1499.00, 1199.00, 100, 0, 1, 'active', 720, 55, 4.30, 20),

-- Fashion (vendor 2 FashionET)
(41, 2, 2, 'Men''s Slim Fit Chinos', 'mens-slim-fit-chinos', 'FE-MSC-005', 'Comfortable stretch chinos perfect for casual and smart-casual looks.', 'Material: Cotton Stretch\nFit: Slim\nSizes: 28-40', 2299.00, 1899.00, 45, 1, 0, 'active', 380, 14, 4.20, 6),
(42, 2, 2, 'Women''s Denim Jacket', 'womens-denim-jacket', 'FE-WDJ-006', 'Classic blue denim jacket with modern cropped fit.', 'Material: 100% Cotton Denim\nFit: Regular\nSizes: S-XL', 3499.00, 2799.00, 30, 1, 1, 'active', 650, 19, 4.50, 9),
(43, 2, 2, 'Unisex Hoodie Premium', 'unisex-hoodie-premium', 'FE-UHP-007', 'Soft fleece hoodie with kangaroo pocket and drawstring hood.', 'Material: Cotton-Poly Fleece\nFit: Regular\nSizes: S-XXL', 2799.00, NULL, 55, 0, 1, 'active', 520, 22, 4.40, 11),
(44, 2, 2, 'Women''s High-Waist Jeans', 'womens-high-waist-jeans', 'FE-WHJ-008', 'Flattering high-waist skinny jeans with stretch comfort.', 'Material: Stretch Denim\nRise: High\nSizes: 24-34', 2599.00, 2199.00, 40, 0, 0, 'active', 410, 16, 4.30, 7),
(45, 2, 2, 'Men''s Leather Belt Classic', 'mens-leather-belt', 'FE-MLB-009', 'Genuine leather belt with polished metal buckle.', 'Material: Genuine Leather\nWidth: 3.5cm\nSizes: 30-42', 999.00, 799.00, 70, 0, 0, 'active', 290, 25, 4.10, 8),
(46, 2, 2, 'Women''s Ankle Boots', 'womens-ankle-boots', 'FE-WAB-010', 'Stylish block-heel ankle boots for everyday wear.', 'Material: Synthetic Leather\nHeel: 5cm block\nSizes: 36-41', 3999.00, 3299.00, 28, 1, 0, 'active', 580, 11, 4.40, 5),
(47, 2, 2, 'Cotton T-Shirt 3-Pack', 'cotton-tshirt-3pack', 'FE-CTS-011', 'Set of 3 premium cotton crew-neck t-shirts.', 'Material: 100% Cotton\nFit: Regular\nColors: Mixed\nSizes: S-XXL', 1899.00, 1499.00, 80, 0, 1, 'active', 740, 48, 4.20, 18),
(48, 2, 2, 'Women''s Silk Scarf', 'womens-silk-scarf', 'FE-WSS-012', 'Elegant printed silk scarf, perfect accessory.', 'Material: Silk Blend\nSize: 90x90cm\nPrint: Floral', 1299.00, NULL, 35, 0, 0, 'active', 210, 8, 4.50, 3),

-- Home & Living (vendor 3 HomePlus)
(49, 3, 3, 'Memory Foam Pillow Set', 'memory-foam-pillow-set', 'HP-MFP-005', 'Set of 2 ergonomic memory foam pillows for better sleep.', 'Material: Memory Foam\nCover: Removable\nPieces: 2', 2499.00, 1999.00, 40, 1, 1, 'active', 680, 30, 4.60, 15),
(50, 3, 3, 'Stainless Steel Cutlery Set', 'stainless-cutlery-set', 'HP-SCS-006', '24-piece premium stainless steel cutlery set for 6 people.', 'Pieces: 24\nMaterial: 18/10 Stainless\nFinish: Mirror', 1899.00, 1599.00, 35, 0, 0, 'active', 340, 12, 4.30, 6),
(51, 3, 3, 'Aroma Diffuser Ultrasonic', 'aroma-diffuser-ultrasonic', 'HP-ADU-007', 'Ultrasonic essential oil diffuser with LED mood lighting.', 'Capacity: 300ml\nRuntime: 8hrs\nModes: Mist + Light', 1499.00, 1199.00, 50, 0, 1, 'active', 520, 28, 4.40, 12),
(52, 3, 3, 'Cotton Bed Sheet Set Queen', 'cotton-bed-sheet-queen', 'HP-CBS-008', 'Soft 4-piece cotton bed sheet set for queen size bed.', 'Material: 100% Cotton\nPieces: 4\nSize: Queen', 3299.00, 2699.00, 25, 1, 0, 'active', 450, 14, 4.50, 7),
(53, 3, 3, 'Wall Clock Modern Minimal', 'wall-clock-modern', 'HP-WCM-009', 'Silent quartz wall clock with minimalist design.', 'Diameter: 30cm\nMovement: Silent Quartz\nMaterial: ABS', 899.00, NULL, 60, 0, 0, 'active', 280, 18, 4.20, 5),
(54, 3, 3, 'Storage Ottoman Bench', 'storage-ottoman-bench', 'HP-SOB-010', 'Multi-purpose storage ottoman that doubles as seating.', 'Material: Linen + Wood\nSize: 80x40x45cm\nStorage: Yes', 4499.00, 3799.00, 15, 0, 0, 'active', 310, 6, 4.30, 3),
(55, 3, 3, 'Kitchen Knife Set 5pcs', 'kitchen-knife-set-5pcs', 'HP-KKS-011', 'Professional kitchen knife set with wooden block.', 'Pieces: 5 + Block\nMaterial: High Carbon Steel\nHandle: Ergonomic', 2999.00, 2499.00, 30, 1, 1, 'active', 590, 20, 4.60, 10),
(56, 3, 3, 'Bath Towel Set 4pcs', 'bath-towel-set-4pcs', 'HP-BTS-012', 'Absorbent soft cotton bath towel set.', 'Material: 100% Cotton\nPieces: 4\nGSM: 600', 1699.00, 1399.00, 45, 0, 0, 'active', 370, 16, 4.40, 8),

-- Beauty (vendor 4 BeautyBox)
(57, 4, 4, 'Retinol Night Cream 50ml', 'retinol-night-cream', 'BB-RNC-005', 'Anti-aging night cream with pure retinol for smoother skin.', 'Volume: 50ml\nKey: Retinol 0.5%\nSkin: All types', 1299.00, 999.00, 60, 1, 1, 'active', 920, 35, 4.70, 18),
(58, 4, 4, 'Hyaluronic Acid Moisturizer', 'hyaluronic-acid-moisturizer', 'BB-HAM-006', 'Intense hydration cream with multi-weight hyaluronic acid.', 'Volume: 50ml\nKey: HA Complex\nTexture: Lightweight', 1099.00, 899.00, 70, 1, 0, 'active', 780, 28, 4.60, 14),
(59, 4, 4, 'Makeup Brush Set 12pcs', 'makeup-brush-set-12', 'BB-MBS-007', 'Professional makeup brush set with soft synthetic bristles.', 'Pieces: 12\nBristles: Synthetic\nCase: Included', 1999.00, 1599.00, 40, 0, 1, 'active', 610, 22, 4.50, 11),
(60, 4, 4, 'Niacinamide Serum 30ml', 'niacinamide-serum-30ml', 'BB-NS-008', 'Pore-refining serum with 10% niacinamide and zinc.', 'Volume: 30ml\nKey: 10% Niacinamide\nFragrance: Free', 799.00, 649.00, 90, 0, 1, 'active', 1100, 52, 4.80, 30),
(61, 4, 4, 'Lip Balm Natural 3-Pack', 'lip-balm-natural-3pack', 'BB-LB-009', 'Nourishing natural lip balm set with shea and coconut.', 'Pieces: 3\nIngredients: Shea, Coconut, Beeswax', 449.00, NULL, 120, 0, 0, 'active', 340, 40, 4.30, 12),
(62, 4, 4, 'Face Cleansing Brush', 'face-cleansing-brush', 'BB-FCB-010', 'Sonic facial cleansing brush for deep pore cleaning.', 'Modes: 3\nBattery: Rechargeable\nWaterproof: Yes', 2499.00, 1999.00, 25, 1, 0, 'active', 480, 9, 4.40, 5),
(63, 4, 4, 'Eyeshadow Palette 18 Colors', 'eyeshadow-palette-18', 'BB-EP-011', 'Versatile eyeshadow palette with matte and shimmer shades.', 'Shades: 18\nFinish: Matte + Shimmer\nCruelty-free: Yes', 1799.00, 1499.00, 35, 0, 0, 'active', 550, 14, 4.50, 7),
(64, 4, 4, 'Body Lotion Shea Butter 400ml', 'body-lotion-shea-400ml', 'BB-BL-012', 'Rich moisturizing body lotion with pure shea butter.', 'Volume: 400ml\nKey: Shea Butter\nSkin: Dry to Normal', 699.00, 549.00, 80, 0, 1, 'active', 420, 33, 4.40, 15),

-- Sports (vendor 5 SportZone)
(65, 5, 5, 'Running Shoes Lightweight', 'running-shoes-lightweight', 'SZ-RS-005', 'Breathable lightweight running shoes with cushioned sole.', 'Material: Mesh + EVA\nWeight: 250g\nSizes: 36-45', 4499.00, 3799.00, 35, 1, 1, 'active', 890, 24, 4.50, 12),
(66, 5, 5, 'Fitness Tracker Band', 'fitness-tracker-band', 'SZ-FTB-006', 'Smart fitness band with heart rate and sleep tracking.', 'Display: Color AMOLED\nBattery: 14 days\nWaterproof: 5ATM', 2999.00, 2499.00, 45, 1, 0, 'active', 720, 18, 4.30, 9),
(67, 5, 5, 'Jump Rope Speed Pro', 'jump-rope-speed-pro', 'SZ-JR-007', 'Adjustable speed jump rope for cardio workouts.', 'Material: Steel Cable + PVC\nLength: Adjustable\nHandles: Foam', 599.00, 449.00, 100, 0, 1, 'active', 380, 42, 4.20, 16),
(68, 5, 5, 'Gym Gloves Padded', 'gym-gloves-padded', 'SZ-GG-008', 'Padded workout gloves with wrist support.', 'Material: Leather + Neoprene\nSizes: S-XL\nPadding: Gel', 899.00, NULL, 60, 0, 0, 'active', 290, 15, 4.10, 6),
(69, 5, 5, 'Protein Shaker Bottle 700ml', 'protein-shaker-700ml', 'SZ-PS-009', 'Leak-proof protein shaker with mixing ball.', 'Capacity: 700ml\nMaterial: BPA-Free\nFeatures: Mixing ball', 399.00, 299.00, 150, 0, 1, 'active', 510, 65, 4.00, 20),
(70, 5, 5, 'Foam Roller 45cm', 'foam-roller-45cm', 'SZ-FR-010', 'High-density foam roller for muscle recovery.', 'Length: 45cm\nDensity: High\nMaterial: EVA Foam', 1299.00, 999.00, 40, 0, 0, 'active', 340, 11, 4.40, 5),
(71, 5, 5, 'Cycling Helmet Adjustable', 'cycling-helmet-adjustable', 'SZ-CH-011', 'Lightweight cycling helmet with adjustable fit system.', 'Weight: 280g\nVents: 18\nCertification: CE', 2499.00, 1999.00, 25, 1, 0, 'active', 280, 7, 4.50, 3),
(72, 5, 5, 'Sports Duffel Bag 40L', 'sports-duffel-bag-40l', 'SZ-SDB-012', 'Spacious sports duffel with shoe compartment.', 'Capacity: 40L\nMaterial: Polyester\nCompartments: 3', 1999.00, 1599.00, 30, 0, 0, 'active', 360, 10, 4.30, 4),

-- Books (vendor 6 BookWorld)
(73, 6, 6, 'The Psychology of Money', 'psychology-of-money', 'BW-POM-005', 'Timeless lessons on wealth, greed and happiness by Morgan Housel.', 'Author: Morgan Housel\nPages: 256\nFormat: Paperback', 899.00, 749.00, 70, 1, 1, 'active', 1400, 58, 4.90, 32),
(74, 6, 6, 'Rich Dad Poor Dad', 'rich-dad-poor-dad', 'BW-RDP-006', 'What the rich teach their kids about money by Robert Kiyosaki.', 'Author: Robert Kiyosaki\nPages: 336\nFormat: Paperback', 799.00, NULL, 65, 1, 1, 'active', 1200, 45, 4.70, 25),
(75, 6, 6, 'Amharic Children Storybook', 'amharic-children-storybook', 'BW-ACS-007', 'Beautifully illustrated Amharic storybook for young readers.', 'Language: Amharic\nAge: 4-8\nPages: 48\nIllustrated: Yes', 499.00, 399.00, 50, 0, 0, 'active', 320, 18, 4.60, 8),
(76, 6, 6, 'Planner 2026 Hardcover', 'planner-2026-hardcover', 'BW-PL-008', 'Annual planner with monthly and weekly layouts.', 'Year: 2026\nFormat: Hardcover\nPages: 200', 699.00, 549.00, 40, 0, 0, 'active', 280, 12, 4.30, 5),
(77, 6, 6, 'English Grammar Guide', 'english-grammar-guide', 'BW-EGG-009', 'Comprehensive English grammar reference for students.', 'Pages: 320\nLevel: Intermediate\nExercises: Included', 599.00, NULL, 55, 0, 0, 'active', 410, 20, 4.40, 9),
(78, 6, 6, 'Sticky Notes Set Colorful', 'sticky-notes-set', 'BW-SNS-010', 'Pack of colorful sticky notes in multiple sizes.', 'Pieces: 12 pads\nColors: Mixed\nSizes: Various', 299.00, 249.00, 100, 0, 1, 'active', 250, 38, 4.10, 10),
(79, 6, 6, 'Fountain Pen Classic', 'fountain-pen-classic', 'BW-FP-011', 'Elegant fountain pen with smooth ink flow.', 'Nib: Medium\nMaterial: Metal\nInk: Cartridge', 1499.00, 1199.00, 30, 0, 0, 'active', 190, 6, 4.50, 3),
(80, 6, 6, 'World Atlas Illustrated', 'world-atlas-illustrated', 'BW-WA-012', 'Updated world atlas with detailed maps and facts.', 'Pages: 280\nFormat: Hardcover\nMaps: Full color', 1899.00, 1599.00, 20, 1, 0, 'active', 360, 8, 4.60, 4),

-- Food (vendor 8 OrganicFarm)
(81, 8, 7, 'Organic Coffee Beans 500g', 'organic-coffee-beans-500g', 'OF-OCB-005', 'Single-origin Ethiopian coffee beans, medium roast.', 'Weight: 500g\nOrigin: Yirgacheffe\nRoast: Medium\nOrganic: Yes', 899.00, 749.00, 80, 1, 1, 'active', 950, 48, 4.80, 28),
(82, 8, 7, 'Teff Flour Organic 1kg', 'teff-flour-organic-1kg', 'OF-TF-006', 'Premium organic teff flour for injera and baking.', 'Weight: 1kg\nType: Brown Teff\nOrganic: Certified', 449.00, NULL, 100, 0, 1, 'active', 580, 42, 4.50, 18),
(83, 8, 7, 'Natural Shea Butter 250g', 'natural-shea-butter-250g', 'OF-NSB-007', 'Unrefined pure African shea butter for skin and hair.', 'Weight: 250g\nType: Unrefined\nOrigin: Africa', 599.00, 499.00, 70, 0, 0, 'active', 420, 22, 4.60, 11),
(84, 8, 7, 'Dried Spices Gift Box', 'dried-spices-gift-box', 'OF-DSG-008', 'Assorted Ethiopian spices gift set in elegant box.', 'Items: 6 spices\nWeight: ~400g total\nPackaging: Gift box', 1299.00, 999.00, 35, 1, 0, 'active', 380, 14, 4.70, 7),
(85, 8, 7, 'Organic Green Tea 100g', 'organic-green-tea-100g', 'OF-OGT-009', 'Premium loose-leaf organic green tea.', 'Weight: 100g\nType: Loose leaf\nOrganic: Yes', 399.00, 349.00, 90, 0, 0, 'active', 310, 25, 4.40, 9),
(86, 8, 7, 'Honeycomb Raw 300g', 'honeycomb-raw-300g', 'OF-HC-010', 'Natural raw honeycomb straight from the hive.', 'Weight: 300g\nType: Raw Comb\nOrigin: Highlands', 799.00, NULL, 40, 0, 1, 'active', 450, 16, 4.80, 8),
(87, 8, 7, 'Sesame Oil Cold Pressed 500ml', 'sesame-oil-cold-pressed', 'OF-SO-011', 'Cold-pressed pure sesame oil for cooking and health.', 'Volume: 500ml\nMethod: Cold pressed\nPure: 100%', 549.00, 449.00, 55, 0, 0, 'active', 270, 12, 4.30, 5),
(88, 8, 7, 'Mixed Nuts Premium 400g', 'mixed-nuts-premium-400g', 'OF-MN-012', 'Premium mix of almonds, cashews, walnuts and raisins.', 'Weight: 400g\nContents: Almonds, Cashews, Walnuts, Raisins', 999.00, 849.00, 50, 1, 1, 'active', 620, 30, 4.60, 14),

-- More Electronics / Gadgets
(89, 1, 1, 'Webcam 1080p Full HD', 'webcam-1080p-full-hd', 'TH-WC-1080', 'Plug-and-play Full HD webcam with built-in microphone.', 'Resolution: 1080p 30fps\nMic: Dual\nMount: Universal', 2499.00, 1999.00, 40, 0, 0, 'active', 480, 15, 4.30, 7),
(90, 1, 9, 'Mechanical Keyboard RGB', 'mechanical-keyboard-rgb', 'TH-MK-RGB', 'RGB mechanical gaming keyboard with blue switches.', 'Switches: Blue Mechanical\nLayout: Full size\nLighting: RGB', 3999.00, 3299.00, 28, 1, 1, 'active', 870, 20, 4.50, 11),
(91, 7, 1, 'Portable SSD 1TB', 'portable-ssd-1tb', 'GP-SSD-1TB', 'Ultra-fast portable SSD with USB-C connectivity.', 'Capacity: 1TB\nSpeed: up to 1050MB/s\nInterface: USB-C', 8999.00, 7999.00, 22, 1, 0, 'active', 640, 10, 4.70, 6),
(92, 7, 8, 'Wireless Charger 15W', 'wireless-charger-15w', 'GP-WC-15W', 'Fast wireless charging pad compatible with Qi devices.', 'Power: 15W\nCompatibility: Qi\nCable: USB-C included', 1499.00, 1199.00, 55, 0, 1, 'active', 530, 28, 4.20, 12),
(93, 1, 1, 'Smart LED Bulb 2-Pack', 'smart-led-bulb-2pack', 'TH-SLB-2', 'WiFi smart LED bulbs with app and voice control.', 'Pieces: 2\nWattage: 9W\nColors: 16 million\nApp: Yes', 1299.00, 999.00, 70, 0, 0, 'active', 390, 22, 4.40, 9),
(94, 7, 1, 'USB Hub 7-Port', 'usb-hub-7port', 'GP-UH-7', 'Powered 7-port USB 3.0 hub for multiple devices.', 'Ports: 7x USB 3.0\nPower: External adapter\nSpeed: 5Gbps', 1999.00, NULL, 35, 0, 0, 'active', 260, 8, 4.10, 4),

-- More Fashion & Home
(95, 2, 2, 'Men''s Formal Dress Shirt', 'mens-formal-dress-shirt', 'FE-MFD-013', 'Crisp formal shirt ideal for office and events.', 'Material: Cotton\nFit: Slim\nCollar: Spread\nSizes: S-XXL', 2199.00, 1799.00, 40, 0, 0, 'active', 350, 13, 4.30, 6),
(96, 2, 2, 'Women''s Crossbody Mini Bag', 'womens-crossbody-mini-bag', 'FE-WCM-014', 'Compact stylish crossbody bag for essentials.', 'Material: Vegan Leather\nSize: Mini\nStrap: Adjustable', 2499.00, 1999.00, 30, 1, 0, 'active', 480, 11, 4.50, 5),
(97, 3, 3, 'Plant Pot Ceramic Set 3pcs', 'plant-pot-ceramic-set', 'HP-PPC-013', 'Decorative ceramic plant pots in three sizes.', 'Pieces: 3\nMaterial: Ceramic\nDrainage: Yes', 1599.00, 1299.00, 35, 0, 0, 'active', 290, 9, 4.40, 4),
(98, 3, 3, 'Electric Kettle 1.7L', 'electric-kettle-1-7l', 'HP-EK-014', 'Fast-boil electric kettle with auto shut-off.', 'Capacity: 1.7L\nPower: 2200W\nMaterial: Stainless Steel', 1899.00, 1499.00, 40, 0, 1, 'active', 510, 19, 4.50, 8),
(99, 5, 5, 'Yoga Block 2-Pack', 'yoga-block-2pack', 'SZ-YB-013', 'High-density foam yoga blocks for support and balance.', 'Pieces: 2\nMaterial: EVA Foam\nSize: Standard', 799.00, 599.00, 55, 0, 0, 'active', 240, 14, 4.20, 5),
(100, 4, 4, 'Perfume Set Mini 4pcs', 'perfume-set-mini-4pcs', 'BB-PS-013', 'Travel-size perfume discovery set with 4 fragrances.', 'Pieces: 4 x 10ml\nType: Eau de Parfum\nNotes: Mixed', 2499.00, 1999.00, 25, 1, 0, 'active', 670, 12, 4.60, 7);

-- Product images (placeholders)
INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES
(1,'products/p1_1790717429.jpg',1,0),(2,'products/p2.jpg',1,0),(3,'products/p3.jpg',1,0),(4,'products/p4_1790718305.jpg',1,0),
(5,'products/p5.svg',1,0),(6,'products/p6.svg',1,0),(7,'products/p7.svg',1,0),(8,'products/p8.jpg',1,0),
(9,'products/p9.svg',1,0),(10,'products/p10.jpg',1,0),(11,'products/p11.svg',1,0),(12,'products/p12.svg',1,0),
(13,'products/p13.jpg',1,0),(14,'products/p14.jpg',1,0),(15,'products/p15.svg',1,0),(16,'products/p16.svg',1,0),
(17,'products/p17.jpg',1,0),(18,'products/p18.svg',1,0),(19,'products/p19.svg',1,0),(20,'products/p20.svg',1,0),
(21,'products/p21.svg',1,0),(22,'products/p22.jpg',1,0),(23,'products/p23.jpg',1,0),(24,'products/p24_1790717537.jpg',1,0),
(25,'products/p25.svg',1,0),(26,'products/p26.svg',1,0),(27,'products/p27.jpg',1,0),(28,'products/p28.png',1,0),
(29,'products/p29.svg',1,0),(30,'products/p30.svg',1,0),(31,'products/p31.png',1,0),(32,'products/p32.jpg',1,0),
(33,'products/p33_1790717335.jpg',1,0),(34,'products/p34.jpg',1,0),(35,'products/p35.jpg',1,0),(36,'products/p36.svg',1,0),
(37,'products/p37.svg',1,0),(38,'products/p38.svg',1,0),(39,'products/p39.svg',1,0),(40,'products/p40.svg',1,0),
(41,'products/p41.svg',1,0),(42,'products/p42.svg',1,0),(43,'products/p43.jpg',1,0),(44,'products/p44.jpg',1,0),
(45,'products/p45.svg',1,0),(46,'products/p46.svg',1,0),(47,'products/p47.svg',1,0),(48,'products/p48.svg',1,0),
(49,'products/p49.jpg',1,0),(50,'products/p50.svg',1,0),(51,'products/p51.svg',1,0),(52,'products/p52.svg',1,0),
(53,'products/p53.jpg',1,0),(54,'products/p54.svg',1,0),(55,'products/p55.jpg',1,0),(56,'products/p56.svg',1,0),
(57,'products/p57.svg',1,0),(58,'products/p58.svg',1,0),(59,'products/p59.jpg',1,0),(60,'products/p60.svg',1,0),
(61,'products/p61.svg',1,0),(62,'products/p62.svg',1,0),(63,'products/p63.jpg',1,0),(64,'products/p64.svg',1,0),
(65,'products/p65.png',1,0),(66,'products/p66.svg',1,0),(67,'products/p67.svg',1,0),(68,'products/p68.jpg',1,0),
(69,'products/p69.svg',1,0),(70,'products/p70.svg',1,0),(71,'products/p71.svg',1,0),(72,'products/p72.svg',1,0),
(73,'products/p73.svg',1,0),(74,'products/p74.svg',1,0),(75,'products/p75.svg',1,0),(76,'products/p76.svg',1,0),
(77,'products/p77.svg',1,0),(78,'products/p78.jpg',1,0),(79,'products/p79.svg',1,0),(80,'products/p80.svg',1,0),
(81,'products/p81.jpg',1,0),(82,'products/p82.svg',1,0),(83,'products/p83.jpg',1,0),(84,'products/p84.svg',1,0),
(85,'products/p85.svg',1,0),(86,'products/p86.svg',1,0),(87,'products/p87.svg',1,0),(88,'products/p88.jpg',1,0),
(89,'products/p89_1790717211.jpg',1,0),(90,'products/p90_1790716938.jpg',1,0),(91,'products/p91.jpg',1,0),(92,'products/p92.jpg',1,0),
(93,'products/p93.jpg',1,0),(94,'products/p94.jpg',1,0),(95,'products/p95.svg',1,0),(96,'products/p96.svg',1,0),
(97,'products/p97.svg',1,0),(98,'products/p98.jpg',1,0),(99,'products/p99.jpg',1,0),(100,'products/p100.svg',1,0);
