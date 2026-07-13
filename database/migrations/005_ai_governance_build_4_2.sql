-- EduDirectionAI Enterprise Build 4.2
-- AI Governance Platform migration

ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS academic_year VARCHAR(20) NULL AFTER dataset_name;
ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS prediction_title VARCHAR(255) NULL AFTER academic_year;
ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS lifecycle_status ENUM('active','inactive','archived','deleted') DEFAULT 'inactive' AFTER status;
ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS dataset_hash VARCHAR(64) NULL AFTER dataset_path;
ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER completed_at;
ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER archived_at;
ALTER TABLE prediction_batches ADD INDEX IF NOT EXISTS idx_batch_year(academic_year);
ALTER TABLE prediction_batches ADD INDEX IF NOT EXISTS idx_batch_lifecycle(lifecycle_status);
ALTER TABLE prediction_batches ADD INDEX IF NOT EXISTS idx_batch_hash(dataset_hash);

CREATE TABLE IF NOT EXISTS dataset_registry (
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

CREATE TABLE IF NOT EXISTS model_training_jobs (
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

ALTER TABLE research_models ADD COLUMN IF NOT EXISTS base_model_id INT NULL AFTER id;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS training_source VARCHAR(255) NULL AFTER model_path;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS status ENUM('active','inactive','archived','training','failed') DEFAULT 'inactive' AFTER is_active;
