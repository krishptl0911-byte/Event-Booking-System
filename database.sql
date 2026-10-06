-- =====================================================
-- EventHub : Online Event Booking System (MySQL)
-- Import in phpMyAdmin  OR  run: mysql -u root < database.sql
-- =====================================================
DROP DATABASE IF EXISTS event_booking;
CREATE DATABASE event_booking CHARACTER SET utf8mb4;
USE event_booking;

-- 1. USERS : everyone who can log in (role decides access)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. EVENTS : created and managed by admin
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(50) NOT NULL,
  venue VARCHAR(150) NOT NULL,
  event_date DATETIME NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_seats INT NOT NULL,
  available_seats INT NOT NULL,
  image_url VARCHAR(255)
);

-- 3. BOOKINGS : one row per booking (user + event + quantity)
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  quantity INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  booking_code VARCHAR(30) NOT NULL UNIQUE,
  checked_in TINYINT(1) DEFAULT 0,
  checked_in_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (event_id) REFERENCES events(id)
);

-- 4. PAYMENTS : one row per (simulated) payment
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(30) NOT NULL,
  status VARCHAR(20) DEFAULT 'success',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- ---------------- SAMPLE DATA ----------------
-- Admin  : admin@events.com / admin123
-- Users  : riya@example.com / user123 , amit@example.com / user123
INSERT INTO users (name,email,password_hash,role) VALUES
('Admin','admin@events.com','$2y$10$.8FlYrk0P4EuROQ/BMbkn..IH8N6IGkGAMZ83YghDJhWpKBbtrysO','admin'),
('Riya Patel','riya@example.com','$2y$10$sStx2S5Ru/BoEBtltg.f7uvqsL/1cOZME78aN4tbdU/4crjQWoDZG','user'),
('Amit Shah','amit@example.com','$2y$10$96o.ezTd/GoYuSjRyQi/.OI.VEhXXdDI4KYVcUWm9SlsQfwR3mN9O','user');

INSERT INTO events (title,description,category,venue,event_date,price,total_seats,available_seats,image_url) VALUES
('Rock Night Live','An electrifying evening with the best local rock bands. Food stalls and merchandise available.','Concert','City Arena, Ahmedabad','2026-12-05 19:00:00',499,200,198,''),
('Web Development Workshop','Hands-on one-day workshop covering HTML, CSS, JavaScript and PHP with live projects.','Workshop','Tech Hub, Vadodara','2026-11-15 10:00:00',199,50,49,''),
('Startup Seminar','Founders share real stories, mistakes and lessons about building startups. Free entry.','Seminar','Convention Hall, Surat','2026-11-22 11:00:00',0,100,100,''),
('Inter-College Cricket Cup','Final match of the inter-college cricket tournament. Come and cheer for your college.','Sports','University Ground, Anand','2026-12-12 15:00:00',99,300,300,''),
('Cultural Fest 2026','Dance, music, drama and food stalls all day long.','College Fest','Main Campus, Petlad','2026-12-20 09:00:00',149,500,497,''),
('Stand-up Comedy Night','Laugh out loud with top stand-up comedians from across India.','Concert','Town Hall, Ahmedabad','2026-11-30 20:00:00',350,150,150,''),
('AI & Robotics Expo','Live robot demos, project showcases and talks by industry experts.','Seminar','Science City, Ahmedabad','2027-01-10 10:00:00',250,120,120,''),
('Photography Walk','Guided morning photo walk through the old city with a professional photographer.','Workshop','Manek Chowk, Ahmedabad','2026-11-29 06:30:00',120,30,30,'');

INSERT INTO bookings (user_id,event_id,quantity,total_amount,status,booking_code,checked_in,checked_in_at) VALUES
(2,1,2,998.00,'confirmed','EVT-A1B2C3D4',0,NULL),
(2,2,1,199.00,'confirmed','EVT-E5F6G7H8',1,'2026-10-06 18:30:00'),
(3,5,3,447.00,'confirmed','EVT-J9K1L2M3',0,NULL),
(3,4,2,198.00,'cancelled','EVT-N4P5Q6R7',0,NULL);

INSERT INTO payments (booking_id,amount,method,status) VALUES
(1,998.00,'UPI','success'),
(2,199.00,'Card','success'),
(3,447.00,'UPI','success');

-- ---------------- USEFUL QUERIES FOR VIVA ----------------
-- 1. All bookings with attendee, event, and check-in status (INNER JOIN):
--   SELECT b.booking_code, u.name, e.title, b.quantity, b.status, b.checked_in
--   FROM bookings b
--   JOIN users u ON b.user_id = u.id
--   JOIN events e ON b.event_id = e.id;
--
-- 2. Total revenue collected per event (JOIN + GROUP BY + SUM):
--   SELECT e.title, SUM(p.amount) AS revenue
--   FROM payments p
--   JOIN bookings b ON p.booking_id = b.id
--   JOIN events e ON b.event_id = e.id
--   WHERE p.status = 'success'
--   GROUP BY e.id;
--
-- 3. Check-in summary per event (conditional aggregation):
--   SELECT e.title, COUNT(b.id) AS confirmed_bookings,
--          SUM(CASE WHEN b.checked_in = 1 THEN 1 ELSE 0 END) AS checked_in_count
--   FROM events e
--   JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
--   GROUP BY e.id;
