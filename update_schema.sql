ALTER TABLE members MODIFY COLUMN status ENUM('Active', 'Inactive', 'Visitor', 'Affiliate Community Member') DEFAULT 'Active';
UPDATE members SET status='Affiliate Community Member' WHERE status='Visitor';
ALTER TABLE members MODIFY COLUMN status ENUM('Active', 'Inactive', 'Affiliate Community Member') DEFAULT 'Active';

ALTER TABLE attendance_records MODIFY COLUMN status ENUM('Present', 'Absent', 'Visitor', 'Affiliate Community Member') DEFAULT 'Present';
UPDATE attendance_records SET status='Affiliate Community Member' WHERE status='Visitor';
ALTER TABLE attendance_records MODIFY COLUMN status ENUM('Present', 'Absent', 'Affiliate Community Member') DEFAULT 'Present';
