-- ============================================================
--  HOG-CCR Management System — MySQL Database Schema
--  Database: hog_ccr
--  Run this file once to create all tables.
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS hog_ccr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hog_ccr;

-- ─────────────────────────────────────────────
-- 1. SYSTEM ADMINS (login accounts)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100)  NOT NULL,
  username     VARCHAR(50)   NOT NULL UNIQUE,
  email        VARCHAR(150)  NOT NULL UNIQUE,
  password     VARCHAR(255)  NOT NULL,  -- bcrypt hash
  role         ENUM('Administrator','Secretary','Finance Secretary') DEFAULT 'Secretary',
  initials     VARCHAR(5),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account (password: Admin@2026)
-- Replace hash with: password_hash('Admin@2026', PASSWORD_BCRYPT)
INSERT INTO admins (name, username, email, password, role, initials) VALUES
('Elder Asante', 'admin', 'admin@hogccr.org',
 '$2y$12$Q7H3k2X0nVm8oP9wL1dRsOFjZy4bA6tIuWq5NcGeDhMxKp3sY1ve2',
 'Administrator', 'EA')
ON DUPLICATE KEY UPDATE id = id;

-- ─────────────────────────────────────────────
-- 2. MINISTRIES (referenced by members)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ministries (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(50)   NOT NULL UNIQUE,
  name         VARCHAR(100)  NOT NULL,
  description  TEXT,
  icon         VARCHAR(10)   DEFAULT '✝️',
  bg_color     VARCHAR(30)   DEFAULT 'var(--gold-pale)',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO ministries (slug, name, description, icon, bg_color) VALUES
('music',        'Music Ministry',  'Worship & praise team',       '🎵', 'var(--gold-pale)'),
('intercessory', 'Intercessory',    'Prayer warriors team',        '🙏', '#EEF2FF'),
('evangelism',   'Evangelism',      'Outreach & missions',         '🌍', '#FEF9EC'),
('youth',        'Youth Wing',      'Young adults 13–35',          '⚡', '#ECFDF5'),
('prayer',       'Prayer Group',    'General prayer cell',         '✝️', 'var(--gold-pale)'),
('executives',   'Executives',      'Leadership & governance',     '👑', '#F5F3FF')
ON DUPLICATE KEY UPDATE id = id;

-- ─────────────────────────────────────────────
-- 3. MEMBERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS members (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_code  VARCHAR(20)   NOT NULL UNIQUE,   -- e.g. CCR-001
  first_name   VARCHAR(100)  NOT NULL,
  last_name    VARCHAR(100)  NOT NULL,
  gender       ENUM('Male','Female') NOT NULL,
  phone        VARCHAR(20),
  email        VARCHAR(150),
  dob          DATE,
  address      TEXT,
  ministry_id  INT UNSIGNED  NULL,
  status       ENUM('Active','Inactive','Visitor') DEFAULT 'Active',
  photo_path   VARCHAR(255),                    -- e.g. assets/images/members/CCR-001.jpg
  joined_date  DATE,
  notes        TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_member_ministry FOREIGN KEY (ministry_id)
    REFERENCES ministries(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 4. MEMBER SACRAMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS member_sacraments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id   INT UNSIGNED NOT NULL,
  sacrament   ENUM('Baptised','Confirmed','First Communion','Matrimony','Orders') NOT NULL,
  received_on DATE,
  UNIQUE KEY uniq_member_sacrament (member_id, sacrament),
  CONSTRAINT fk_sacr_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 5. EVENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200)  NOT NULL,
  description  TEXT,
  venue        VARCHAR(200),
  event_date   DATE          NOT NULL,
  event_time   TIME,
  type         ENUM('Weekly','Monthly','Annual','Special','Service','Meeting','Convention','Retreat','Special Program') DEFAULT 'Weekly',
  target_group VARCHAR(100)  DEFAULT 'All Members',
  created_by   INT UNSIGNED  NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_admin FOREIGN KEY (created_by)
    REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 6. ANNOUNCEMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200)  NOT NULL,
  description  TEXT,
  pinned       TINYINT(1)    DEFAULT 0,
  posted_by    INT UNSIGNED  NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_announce_admin FOREIGN KEY (posted_by)
    REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 7. ATTENDANCE SESSIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance_sessions (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_type VARCHAR(100)  NOT NULL,
  session_date DATE          NOT NULL,
  session_time TIME,
  recorded_by  INT UNSIGNED  NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_session (session_type, session_date),
  CONSTRAINT fk_session_admin FOREIGN KEY (recorded_by)
    REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 8. ATTENDANCE RECORDS (per member per session)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance_records (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id     INT UNSIGNED NOT NULL,
  member_id      INT UNSIGNED NOT NULL,
  status         ENUM('Present','Absent','Visitor') DEFAULT 'Present',
  check_in_time  TIME,
  UNIQUE KEY uniq_record (session_id, member_id),
  CONSTRAINT fk_rec_session FOREIGN KEY (session_id)
    REFERENCES attendance_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_member  FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 9. FINANCE TRANSACTIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS finance_transactions (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id        INT UNSIGNED NULL,
  member_name      VARCHAR(200),               -- for non-registered payers
  type             ENUM('Tithe','Offering','Donation','Pledge','Project Contribution','Welfare') NOT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  payment_method   ENUM('Cash','MoMo','Bank Transfer','Cheque') DEFAULT 'Cash',
  reference_no     VARCHAR(100),
  phone            VARCHAR(20),
  email            VARCHAR(150),
  notes            TEXT,
  transaction_date DATE NOT NULL,
  receipt_sent     TINYINT(1) DEFAULT 0,
  recorded_by      INT UNSIGNED NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_txn_member  FOREIGN KEY (member_id)   REFERENCES members(id) ON DELETE SET NULL,
  CONSTRAINT fk_txn_admin   FOREIGN KEY (recorded_by) REFERENCES admins(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 10. FINANCE TARGETS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS finance_targets (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  target_month   DATE          NOT NULL UNIQUE,   -- stored as YYYY-MM-01
  target_amount  DECIMAL(12,2) NOT NULL,
  notes          TEXT,
  set_by         INT UNSIGNED  NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_target_admin FOREIGN KEY (set_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 11. WELFARE MEMBERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS welfare_members (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id      INT UNSIGNED NOT NULL UNIQUE,
  enrol_date     DATE         NOT NULL,
  monthly_amount DECIMAL(10,2) DEFAULT 0.00,
  notes          TEXT,
  enrolled_by    INT UNSIGNED NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wm_member  FOREIGN KEY (member_id)   REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_wm_admin   FOREIGN KEY (enrolled_by) REFERENCES admins(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 12. WELFARE CONTRIBUTIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS welfare_contributions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  welfare_id     INT UNSIGNED  NOT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash','MoMo','Bank Transfer','Cheque') DEFAULT 'Cash',
  reference_no   VARCHAR(100),
  payment_date   DATE          NOT NULL,
  notes          TEXT,
  notif_sent     TINYINT(1)    DEFAULT 0,
  recorded_by    INT UNSIGNED  NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wc_welfare FOREIGN KEY (welfare_id)  REFERENCES welfare_members(id) ON DELETE CASCADE,
  CONSTRAINT fk_wc_admin   FOREIGN KEY (recorded_by) REFERENCES admins(id)          ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 13. ACTIVITY LOG
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NULL,
  action      VARCHAR(500) NOT NULL,
  module      VARCHAR(50),
  ip_address  VARCHAR(45),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- 14. MESSAGE QUEUE (Background SMS & Email)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS message_queue (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type           ENUM('email', 'sms') NOT NULL,
  recipient      VARCHAR(150) NOT NULL,
  recipient_name VARCHAR(150),
  subject        VARCHAR(200),
  body           TEXT NOT NULL,
  status         ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
  error_log      TEXT,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
