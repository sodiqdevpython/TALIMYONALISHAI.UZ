-- EduDirectionAI Enterprise 4.0
-- Student Master Registry migration

CREATE TABLE IF NOT EXISTS master_students (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  national_student_id VARCHAR(100) NULL,
  pinfl VARCHAR(30) NULL,
  passport_no VARCHAR(50) NULL,
  fio VARCHAR(255) NOT NULL,
  birth_date DATE NULL,
  gender VARCHAR(20) NULL,
  identity_hash VARCHAR(64) NOT NULL,
  status ENUM('active','inactive','merged') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_master_identity_hash(identity_hash),
  INDEX idx_master_national(national_student_id),
  INDEX idx_master_pinfl(pinfl),
  INDEX idx_master_fio(fio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_school_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  master_student_id BIGINT NOT NULL,
  school_id INT NOT NULL,
  batch_id INT NULL,
  class_name VARCHAR(50) NULL,
  academic_year VARCHAR(20) NULL,
  source_prediction_id BIGINT NULL,
  event_type ENUM('study','transfer_in','transfer_out','prediction_snapshot') DEFAULT 'prediction_snapshot',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (master_student_id) REFERENCES master_students(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_ssh_master(master_student_id),
  INDEX idx_ssh_school(school_id),
  INDEX idx_ssh_batch(batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE students ADD COLUMN IF NOT EXISTS master_student_id BIGINT NULL AFTER id;
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_students_master(master_student_id);

ALTER TABLE student_predictions ADD COLUMN IF NOT EXISTS master_student_id BIGINT NULL AFTER student_id;
ALTER TABLE student_predictions ADD INDEX IF NOT EXISTS idx_pred_master(master_student_id);
