CREATE DATABASE IF NOT EXISTS domuscore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE domuscore;

CREATE TABLE agencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  name VARCHAR(150),
  email VARCHAR(255) UNIQUE,
  password_hash VARCHAR(255),
  role ENUM('superadmin','agency_admin','agent','accountant','viewer') DEFAULT 'agent',
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
);

CREATE TABLE properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  title VARCHAR(255),
  description TEXT,
  address VARCHAR(255),
  city VARCHAR(100),
  province VARCHAR(50),
  cap VARCHAR(10),
  price DECIMAL(12,2),
  type VARCHAR(50),
  status ENUM('available','booked','rented','sold') DEFAULT 'available',
  geo_lat DECIMAL(10,7),
  geo_lng DECIMAL(10,7),
  created_by INT,
  main_image_path VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

CREATE TABLE property_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT,
  path VARCHAR(255),
  alt VARCHAR(255),
  priority INT DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE property_shares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT,
  from_agency_id INT,
  to_agency_id INT,
  status ENUM('requested','accepted','rejected','revoked') DEFAULT 'requested',
  permissions JSON DEFAULT NULL,
  note TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  actioned_at DATETIME NULL,
  created_by INT,
  actioned_by INT,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT,
  name VARCHAR(150),
  email VARCHAR(255),
  phone VARCHAR(50),
  source VARCHAR(100),
  property_id INT NULL,
  status VARCHAR(50) DEFAULT 'new',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
);

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  agency_id INT,
  action VARCHAR(255),
  meta JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for search/filter performance
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_city ON properties(city);
CREATE INDEX idx_properties_deleted ON properties(deleted_at);
CREATE INDEX idx_properties_main_image ON properties(main_image_path);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_deleted ON leads(deleted_at);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);
