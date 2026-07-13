-- EduDirectionAI Professional v5.0 database
CREATE DATABASE IF NOT EXISTS edudirectionai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE edudirectionai_db;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS activity_logs, login_logs, password_resets, student_predictions, prediction_batches, students, teacher_classes, classes, users, schools, research_models, roles, system_settings;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_key VARCHAR(50) NOT NULL UNIQUE,
  role_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id, role_key, role_name) VALUES
(1,'super_admin','Super Admin'),
(2,'director','Direktor'),
(3,'vice_director','Zavuch'),
(4,'teacher','O‘qituvchi'),
(5,'student','O‘quvchi');

CREATE TABLE schools (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_code VARCHAR(50) NOT NULL UNIQUE,
  school_name VARCHAR(255) NOT NULL,
  director_name VARCHAR(255),
  region VARCHAR(120),
  district VARCHAR(120),
  address TEXT,
  phone VARCHAR(50),
  email VARCHAR(150),
  status ENUM('active','inactive','blocked') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_school_region(region),
  INDEX idx_school_district(district),
  INDEX idx_school_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NULL,
  role_id INT NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  email VARCHAR(150),
  status ENUM('active','inactive','blocked') DEFAULT 'active',
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_users_school(school_id),
  INDEX idx_users_role(role_id),
  INDEX idx_users_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  batch_id INT NULL,
  class_name VARCHAR(50) NOT NULL,
  academic_year VARCHAR(20),
  curator_teacher_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (curator_teacher_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY uq_school_class_year(school_id, class_name, academic_year),
  INDEX idx_classes_school(school_id),
  INDEX idx_classes_batch(batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE teacher_classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  batch_id INT NULL,
  class_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_teacher_class(teacher_id, class_id),
  INDEX idx_teacher_classes_batch(batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  batch_id INT NULL,
  class_id INT NULL,
  student_code VARCHAR(100) NOT NULL,
  external_student_code VARCHAR(100) NULL,
  student_name VARCHAR(255) NOT NULL,
  username VARCHAR(100) NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  status ENUM('active','inactive','graduated','blocked') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY uq_school_student_code(school_id, student_code),
  INDEX idx_students_school(school_id),
  INDEX idx_students_batch(batch_id),
  INDEX idx_students_class(class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE research_models (
  id INT AUTO_INCREMENT PRIMARY KEY,
  base_model_id INT NULL,
  model_code VARCHAR(100) NOT NULL UNIQUE,
  model_name VARCHAR(255) NOT NULL,
  model_version VARCHAR(50) NOT NULL,
  model_path VARCHAR(500),
  training_source VARCHAR(255) NULL,
  metrics_json LONGTEXT,
  accuracy DECIMAL(8,4) NULL,
  confidence DECIMAL(8,4) NULL,
  f1_score DECIMAL(8,4) NULL,
  roc_auc DECIMAL(8,4) NULL,
  training_samples INT NULL,
  is_active TINYINT(1) DEFAULT 1,
  status ENUM('active','inactive','archived','training','failed') DEFAULT 'active',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO research_models (model_code, model_name, model_version, model_path, is_active)
VALUES ('EDU_PRO_V4_1','EduDirectionAI Professional','v4.1','outputs/models',1);

CREATE TABLE prediction_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  model_id INT NULL,
  uploaded_by INT NULL,
  dataset_name VARCHAR(255),
  academic_year VARCHAR(20) NULL,
  prediction_title VARCHAR(255) NULL,
  dataset_path VARCHAR(500),
  dataset_hash VARCHAR(64) NULL,
  mode ENUM('research','predict') DEFAULT 'predict',
  students_count INT DEFAULT 0,
  mean_confidence FLOAT NULL,
  temporal_coverage_mean FLOAT NULL,
  status ENUM('created','processing','completed','failed') DEFAULT 'created',
  lifecycle_status ENUM('active','inactive','archived','deleted') DEFAULT 'inactive',
  is_active TINYINT(1) DEFAULT 0,
  error_message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  archived_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (model_id) REFERENCES research_models(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_batch_school(school_id),
  INDEX idx_batch_status(status),
  INDEX idx_batch_year(academic_year),
  INDEX idx_batch_lifecycle(lifecycle_status),
  INDEX idx_batch_hash(dataset_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_predictions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  school_id INT NOT NULL,
  student_id INT NULL,
  external_student_code VARCHAR(100),
  student_name VARCHAR(255),
  class_name VARCHAR(50),
  recommended_direction VARCHAR(100),
  alternative_direction VARCHAR(100),
  recommendation_confidence FLOAT,
  raw_model_confidence FLOAT,
  alternative_confidence FLOAT,
  IT_Index FLOAT,
  Engineering_Index FLOAT,
  Medicine_Index FLOAT,
  Economics_Index FLOAT,
  Pedagogy_Index FLOAT,
  academic_mean FLOAT,
  academic_std FLOAT,
  growth_trend FLOAT,
  learning_dynamics FLOAT,
  academic_stability FLOAT,
  temporal_consistency FLOAT,
  temporal_years_count INT,
  temporal_coverage_ratio FLOAT,
  temporal_coverage_level VARCHAR(50),
  recommendation_reason TEXT,
  selected_direction_advice TEXT,
  full_json LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (batch_id) REFERENCES prediction_batches(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_pred_batch(batch_id),
  INDEX idx_pred_school(school_id),
  INDEX idx_pred_student(student_id),
  INDEX idx_pred_direction(recommended_direction),
  INDEX idx_pred_class(class_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE dataset_registry (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  batch_id INT NULL,
  dataset_name VARCHAR(255),
  academic_year VARCHAR(20),
  dataset_hash VARCHAR(64) NOT NULL,
  file_size BIGINT NULL,
  students_count INT DEFAULT 0,
  quality_json LONGTEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dataset_hash_school(school_id,dataset_hash),
  INDEX idx_dataset_school(school_id),
  INDEX idx_dataset_year(academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE model_training_jobs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  base_model_id INT NULL,
  prediction_batch_id INT NULL,
  new_model_id INT NULL,
  status ENUM('created','queued','training','completed','failed','rejected') DEFAULT 'created',
  metrics_before LONGTEXT NULL,
  metrics_after LONGTEXT NULL,
  recommendation VARCHAR(100) NULL,
  log_text LONGTEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  INDEX idx_mtj_batch(prediction_batch_id),
  INDEX idx_mtj_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  username VARCHAR(100),
  role_id INT NULL,
  ip_address VARCHAR(60),
  user_agent TEXT,
  success TINYINT(1) DEFAULT 0,
  message VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  school_id INT NULL,
  action VARCHAR(120) NOT NULL,
  description TEXT,
  entity_type VARCHAR(100),
  entity_id VARCHAR(100),
  ip_address VARCHAR(60),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('system_name','EduDirectionAI Professional v5.0','Platforma nomi'),
('active_model_code','EDU_PRO_V4_1','Faol model kodi'),
('default_language','uz','Standart til');

INSERT INTO schools (id, school_code, school_name, director_name, region, district, address, phone, email)
VALUES (1,'DEMO-001','Demo maktab','Demo Direktor','Sirdaryo','Guliston','Demo manzil','+998901234567','demo@school.local');

-- Demo parollar keyingi modulda PHP password_hash orqali yangilanadi.
INSERT INTO users (id, school_id, role_id, full_name, username, password_hash, status) VALUES
(1,NULL,1,'Super Admin','admin','$2y$10$change_this_hash_after_install','active'),
(2,1,2,'Demo Direktor','director_demo','$2y$10$change_this_hash_after_install','active'),
(3,1,3,'Demo Zavuch','vice_demo','$2y$10$change_this_hash_after_install','active'),
(4,1,4,'Demo O‘qituvchi','teacher_demo','$2y$10$change_this_hash_after_install','active');
