CREATE DATABASE IF NOT EXISTS waste_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE waste_system;

-- =========================================
-- ตารางสมาชิก
-- =========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    points_balance INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- ตารางประเภทขยะ
-- =========================================
CREATE TABLE IF NOT EXISTS waste_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- ตารางรายการรับซื้อ
-- =========================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_points INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- ตารางรายละเอียดรายการรับซื้อ
-- =========================================
CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    category_id INT NOT NULL,
    weight_kg DECIMAL(10,3) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_items_transaction
        FOREIGN KEY (transaction_id)
        REFERENCES transactions(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id)
        REFERENCES waste_categories(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- ตารางประวัติแต้ม
-- =========================================
CREATE TABLE IF NOT EXISTS point_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    ref_transaction_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_point_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- ตารางของรางวัล
-- =========================================
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    points_required INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- ตารางแลกของรางวัล
-- =========================================
CREATE TABLE IF NOT EXISTS redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    points_used INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_redemptions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_redemptions_reward
        FOREIGN KEY (reward_id)
        REFERENCES rewards(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- ข้อมูลประเภทขยะเริ่มต้น
-- =========================================
INSERT INTO waste_categories (name, unit_price)
SELECT 'กระดาษ', 3.50
WHERE NOT EXISTS (
    SELECT 1 FROM waste_categories WHERE name = 'กระดาษ'
);

INSERT INTO waste_categories (name, unit_price)
SELECT 'พลาสติกขวด PET', 8.00
WHERE NOT EXISTS (
    SELECT 1 FROM waste_categories WHERE name = 'พลาสติกขวด PET'
);

INSERT INTO waste_categories (name, unit_price)
SELECT 'เหล็ก', 6.50
WHERE NOT EXISTS (
    SELECT 1 FROM waste_categories WHERE name = 'เหล็ก'
);

INSERT INTO waste_categories (name, unit_price)
SELECT 'อลูมิเนียม', 45.00
WHERE NOT EXISTS (
    SELECT 1 FROM waste_categories WHERE name = 'อลูมิเนียม'
);

INSERT INTO waste_categories (name, unit_price)
SELECT 'ทองแดง', 250.00
WHERE NOT EXISTS (
    SELECT 1 FROM waste_categories WHERE name = 'ทองแดง'
);

-- =========================================
-- สมาชิกตัวอย่าง
-- =========================================
INSERT INTO users (full_name, phone, points_balance)
SELECT 'สมชาย ใจดี', '0812345678', 0
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE phone = '0812345678'
);

-- =========================================
-- ของรางวัลตัวอย่าง
-- =========================================
INSERT INTO rewards
    (reward_name, description, points_required, stock)
SELECT
    'ถุงผ้ารักษ์โลก',
    'ถุงผ้าสำหรับใช้แทนถุงพลาสติก',
    50,
    20
WHERE NOT EXISTS (
    SELECT 1 FROM rewards
    WHERE reward_name = 'ถุงผ้ารักษ์โลก'
);

INSERT INTO rewards
    (reward_name, description, points_required, stock)
SELECT
    'แก้วน้ำรักษ์โลก',
    'แก้วน้ำสำหรับใช้ซ้ำ',
    100,
    10
WHERE NOT EXISTS (
    SELECT 1 FROM rewards
    WHERE reward_name = 'แก้วน้ำรักษ์โลก'
);

INSERT INTO rewards
    (reward_name, description, points_required, stock)
SELECT
    'ร่มรักษ์โลก',
    'ร่มสำหรับสมาชิก',
    200,
    5
WHERE NOT EXISTS (
    SELECT 1 FROM rewards
    WHERE reward_name = 'ร่มรักษ์โลก'
);