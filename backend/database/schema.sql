-- =============================================================
-- SmartLib — Database Schema
-- Run once in phpMyAdmin (or `mysql -u root smartlib < schema.sql`)
-- after creating the empty `smartlib` database.
-- =============================================================

CREATE DATABASE IF NOT EXISTS smartlib
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE smartlib;

-- Idempotent reset (safe in dev — DO NOT run on production data)
DROP TABLE IF EXISTS borrow_records;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS members;

-- -------------------------------------------------------------
-- books — catalogue
-- -------------------------------------------------------------
CREATE TABLE books (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255) NOT NULL,
  author          VARCHAR(255) NOT NULL,
  isbn            CHAR(13) UNIQUE,
  category        VARCHAR(100),
  stock           INT NOT NULL DEFAULT 1,
  available_count INT NOT NULL DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_title    (title),
  INDEX idx_category (category),
  CHECK (stock >= 0),
  CHECK (available_count >= 0),
  CHECK (available_count <= stock)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- members — users (students + admins)
-- -------------------------------------------------------------
CREATE TABLE members (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(255) NOT NULL,
  email         VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,    -- bcrypt via password_hash()
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- borrow_records — borrow/return history
-- -------------------------------------------------------------
CREATE TABLE borrow_records (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT NOT NULL,
  book_id     INT NOT NULL,
  borrow_date DATE NOT NULL,
  due_date    DATE NOT NULL,                  -- borrow_date + BORROW_DAYS
  return_date DATE DEFAULT NULL,              -- NULL = still active
  status      ENUM('active','returned','overdue') NOT NULL DEFAULT 'active',
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id)   REFERENCES books(id)   ON DELETE RESTRICT,
  INDEX idx_member (member_id),
  INDEX idx_book   (book_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Optional helper: a daily scheduled event would flip stale
-- 'active' records past due_date to 'overdue'. For the course
-- project, this can run on demand instead:
--
--   UPDATE borrow_records
--      SET status = 'overdue'
--    WHERE status = 'active' AND due_date < CURDATE();
-- -------------------------------------------------------------
