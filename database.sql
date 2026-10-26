CREATE DATABASE IF NOT EXISTS ruang_seduh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ruang_seduh;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(40) NOT NULL UNIQUE,
 email VARCHAR(160) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 full_name VARCHAR(100) NOT NULL,
 role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
 status TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(70) NOT NULL UNIQUE,
 slug VARCHAR(90) NOT NULL UNIQUE,
 status TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL,
 price INT UNSIGNED NOT NULL,
 cost_price INT UNSIGNED NOT NULL DEFAULT 0,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 unit VARCHAR(20) NOT NULL DEFAULT 'porsi',
 status TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_number VARCHAR(40) NOT NULL UNIQUE,
 user_id BIGINT UNSIGNED NOT NULL,
 order_type ENUM('Dine-In','Takeaway') NOT NULL,
 table_number VARCHAR(20) NULL,
 customer_name VARCHAR(100) NULL,
 subtotal INT UNSIGNED NOT NULL,
 tax_amount INT UNSIGNED NOT NULL DEFAULT 0,
 discount_amount INT UNSIGNED NOT NULL DEFAULT 0,
 grand_total INT UNSIGNED NOT NULL,
 payment_method ENUM('Tunai','QRIS','Transfer Bank','Kartu Debit') NOT NULL,
 amount_paid INT UNSIGNED NOT NULL,
 change_amount INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Diproses',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX idx_orders_created_status(created_at,status),
 INDEX idx_orders_user(user_id)
) ENGINE=InnoDB;

CREATE TABLE order_details (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 product_name VARCHAR(100) NOT NULL,
 unit_price INT UNSIGNED NOT NULL,
 unit_cost INT UNSIGNED NOT NULL DEFAULT 0,
 quantity SMALLINT UNSIGNED NOT NULL,
 line_subtotal INT UNSIGNED NOT NULL,
 special_note VARCHAR(160) NULL,
 CONSTRAINT fk_details_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_details_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
 INDEX idx_details_product(product_id)
) ENGINE=InnoDB;

-- Password demo seluruh akun: password
INSERT INTO users(username,email,password_hash,full_name,role) VALUES
('admin','admin@ruangseduh.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Alya Pramesti','admin'),
('kasir','kasir@ruangseduh.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Raka Putra','kasir');

INSERT INTO categories(name,slug) VALUES
('Coffee','coffee'),('Non-Coffee','non-coffee'),('Food','food'),('Pastry','pastry');

INSERT INTO products(category_id,code,name,price,cost_price,stock,unit) VALUES
(1,'COF-001','Kopi Susu Gula Aren',28000,10000,42,'gelas'),
(1,'COF-002','Es Kopi Ruang',32000,12000,36,'gelas'),
(1,'COF-003','Americano',24000,8000,30,'gelas'),
(1,'COF-004','Cappuccino',30000,11000,24,'gelas'),
(1,'COF-005','V60 Flores',35000,14000,18,'gelas'),
(1,'COF-006','Flat White',32000,12000,20,'gelas'),
(2,'NCF-001','Matcha Oat Latte',34000,14000,22,'gelas'),
(2,'NCF-002','Chocolate Sea Salt',32000,12000,16,'gelas'),
(2,'NCF-003','Teh Rosella Madu',24000,7000,8,'gelas'),
(2,'NCF-004','Yuzu Sparkling',30000,11000,14,'gelas'),
(3,'FOD-001','Chicken Pesto Toast',42000,20000,15,'porsi'),
(3,'FOD-002','Nasi Ayam Sambal Matah',48000,23000,11,'porsi'),
(3,'FOD-003','Mushroom Truffle Pasta',52000,25000,7,'porsi'),
(3,'FOD-004','Roti Bakar Kaya',26000,10000,19,'porsi'),
(4,'PST-001','Butter Croissant',24000,9000,13,'buah'),
(4,'PST-002','Burnt Cheesecake',36000,16000,9,'potong');

-- Sampel pesanan 7 hari terakhir untuk dashboard dan laporan.
INSERT INTO orders(invoice_number,user_id,order_type,table_number,customer_name,subtotal,tax_amount,discount_amount,grand_total,payment_method,amount_paid,change_amount,status,created_at) VALUES
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 6 DAY,'%Y%m%d'),'-0001'),2,'Dine-In','04','Nadia',84000,8400,0,92400,'Tunai',100000,7600,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 6 DAY),' 09:15:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 5 DAY,'%Y%m%d'),'-0001'),2,'Takeaway',NULL,'Bima',62000,6200,5000,63200,'QRIS',63200,0,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 5 DAY),' 10:25:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 4 DAY,'%Y%m%d'),'-0001'),2,'Dine-In','02','Sinta',118000,11800,0,129800,'Kartu Debit',129800,0,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 4 DAY),' 12:40:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 3 DAY,'%Y%m%d'),'-0001'),2,'Dine-In','06','Arman',90000,9000,10000,89000,'Tunai',100000,11000,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 3 DAY),' 13:20:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 2 DAY,'%Y%m%d'),'-0001'),2,'Takeaway',NULL,'Dewi',56000,5600,0,61600,'QRIS',61600,0,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 2 DAY),' 14:05:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE()-INTERVAL 1 DAY,'%Y%m%d'),'-0001'),2,'Dine-In','03','Farah',132000,13200,12000,133200,'Transfer Bank',133200,0,'Selesai',CONCAT(DATE(CURDATE()-INTERVAL 1 DAY),' 15:10:00')),
(CONCAT('INV-POS-',DATE_FORMAT(CURDATE(),'%Y%m%d'),'-0001'),2,'Dine-In','01','Tari',88000,8800,0,96800,'Tunai',100000,3200,'Selesai',CONCAT(CURDATE(),' 08:40:00'));
INSERT INTO order_details(order_id,product_id,product_name,unit_price,quantity,line_subtotal,special_note) VALUES
(1,1,'Kopi Susu Gula Aren',28000,2,56000,NULL),(1,15,'Butter Croissant',28000,1,28000,NULL),
(2,3,'Americano',24000,1,24000,NULL),(2,14,'Roti Bakar Kaya',26000,1,26000,'Tanpa margarin'),(2,15,'Butter Croissant',12000,1,12000,NULL),
(3,2,'Es Kopi Ruang',32000,2,64000,NULL),(3,11,'Chicken Pesto Toast',42000,1,42000,NULL),(3,16,'Burnt Cheesecake',12000,1,12000,NULL),
(4,4,'Cappuccino',30000,2,60000,NULL),(4,14,'Roti Bakar Kaya',30000,1,30000,NULL),
(5,1,'Kopi Susu Gula Aren',28000,1,28000,NULL),(5,7,'Matcha Oat Latte',28000,1,28000,NULL),
(6,5,'V60 Flores',35000,2,70000,NULL),(6,10,'Yuzu Sparkling',30000,1,30000,NULL),(6,16,'Burnt Cheesecake',32000,1,32000,NULL),
(7,1,'Kopi Susu Gula Aren',28000,2,56000,NULL),(7,15,'Butter Croissant',32000,1,32000,NULL);
UPDATE order_details d JOIN products p ON p.id=d.product_id SET d.unit_cost=p.cost_price;
