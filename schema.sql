CREATE DATABASE IF NOT EXISTS medivault;
USE medivault;

CREATE TABLE IF NOT EXISTS admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(25),
  shop_name VARCHAR(150),
  shop_banner_photo_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(25),
  nid_number VARCHAR(50) UNIQUE,
  age INT CHECK (age >= 0),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medicines (
  medicine_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  generic_name VARCHAR(180),
  strength VARCHAR(100),
  dosage_form VARCHAR(80),
  manufacturer VARCHAR(180),
  unit_price DECIMAL(10, 2) NOT NULL CHECK (unit_price >= 0),
  stock_quantity INT NOT NULL DEFAULT 0 CHECK (stock_quantity >= 0),
  reorder_level INT NOT NULL DEFAULT 0 CHECK (reorder_level >= 0),
  requires_prescription BOOLEAN NOT NULL DEFAULT FALSE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS drug_conflicts (
  conflict_id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_id INT NOT NULL,
  conflicting_medicine_id INT NOT NULL,
  severity ENUM('low', 'moderate', 'high', 'contraindicated') NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_drug_conflict_pair UNIQUE (medicine_id, conflicting_medicine_id),
  CONSTRAINT fk_drug_conflicts_medicine
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_drug_conflicts_conflicting_medicine
    FOREIGN KEY (conflicting_medicine_id) REFERENCES medicines(medicine_id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

DROP TRIGGER IF EXISTS trg_drug_conflicts_before_insert;
DROP TRIGGER IF EXISTS trg_drug_conflicts_before_update;

DELIMITER //
CREATE TRIGGER trg_drug_conflicts_before_insert
BEFORE INSERT ON drug_conflicts
FOR EACH ROW
BEGIN
  IF NEW.medicine_id = NEW.conflicting_medicine_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A medicine cannot conflict with itself.';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM drug_conflicts
    WHERE medicine_id = NEW.conflicting_medicine_id
      AND conflicting_medicine_id = NEW.medicine_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reverse duplicate drug conflict is not allowed.';
  END IF;
END//

CREATE TRIGGER trg_drug_conflicts_before_update
BEFORE UPDATE ON drug_conflicts
FOR EACH ROW
BEGIN
  IF NEW.medicine_id = NEW.conflicting_medicine_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A medicine cannot conflict with itself.';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM drug_conflicts
    WHERE medicine_id = NEW.conflicting_medicine_id
      AND conflicting_medicine_id = NEW.medicine_id
      AND conflict_id <> NEW.conflict_id
  ) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reverse duplicate drug conflict is not allowed.';
  END IF;
END//
DELIMITER ;

CREATE TABLE IF NOT EXISTS order_statuses (
  status_id TINYINT PRIMARY KEY,
  status_name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  -- 1 = pending (seeded in order_statuses below)
  status_id TINYINT NOT NULL DEFAULT 1,
  prescription_upload_url VARCHAR(500),
  total_amount DECIMAL(22, 2) NOT NULL DEFAULT 0 CHECK (total_amount >= 0),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_orders_status
    FOREIGN KEY (status_id) REFERENCES order_statuses(status_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  medicine_id INT NOT NULL,
  quantity INT NOT NULL CHECK (quantity > 0),
  unit_price DECIMAL(10, 2) NOT NULL CHECK (unit_price >= 0),
  line_total DECIMAL(22, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_order_items_medicine
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS prescription_reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  reviewed_by_admin_id INT,
  review_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  review_notes TEXT,
  reviewed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prescription_reviews_order
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_prescription_reviews_admin
    FOREIGN KEY (reviewed_by_admin_id) REFERENCES admins(admin_id)
    ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
  log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  user_id INT NULL,
  action_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80),
  entity_id BIGINT,
  details TEXT,
  prescription_review_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_logs_admin
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_activity_logs_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_activity_logs_prescription_review
    FOREIGN KEY (prescription_review_id) REFERENCES prescription_reviews(review_id)
    ON DELETE SET NULL ON UPDATE CASCADE
);

INSERT INTO order_statuses (status_id, status_name) VALUES
  (1, 'pending'),
  (2, 'under_review'),
  (3, 'approved'),
  (4, 'packed'),
  (5, 'shipped'),
  (6, 'delivered'),
  (7, 'cancelled')
ON DUPLICATE KEY UPDATE status_name = VALUES(status_name);
