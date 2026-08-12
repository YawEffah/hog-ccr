ALTER TABLE members MODIFY COLUMN status ENUM('Active', 'Inactive', 'Visitor', 'Affiliate Community Member') DEFAULT 'Active';
UPDATE members SET status='Affiliate Community Member' WHERE status='Visitor';
ALTER TABLE members MODIFY COLUMN status ENUM('Active', 'Inactive', 'Affiliate Community Member') DEFAULT 'Active';

ALTER TABLE attendance_records MODIFY COLUMN status ENUM('Present', 'Absent', 'Visitor', 'Affiliate Community Member') DEFAULT 'Present';
UPDATE attendance_records SET status='Affiliate Community Member' WHERE status='Visitor';
ALTER TABLE attendance_records MODIFY COLUMN status ENUM('Present', 'Absent', 'Affiliate Community Member') DEFAULT 'Present';

-- Added for Notifications Feature
CREATE TABLE IF NOT EXISTS notifications (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id     INT UNSIGNED  NOT NULL,
  type         VARCHAR(50)   NOT NULL,
  title        VARCHAR(200)  NOT NULL,
  message      TEXT          NOT NULL,
  link         VARCHAR(255)  NULL,
  icon         VARCHAR(50)   NULL,
  color        VARCHAR(50)   NULL,
  is_read      TINYINT(1)    DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;
