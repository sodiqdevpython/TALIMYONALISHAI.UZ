-- EduDirectionAI Enterprise 3.1
-- Prediction Isolation Architecture migration
-- Har bir prediction alohida snapshot bo‘lib saqlanishi uchun kerakli ustunlar.

ALTER TABLE classes ADD COLUMN IF NOT EXISTS batch_id INT NULL AFTER school_id;
ALTER TABLE classes ADD INDEX IF NOT EXISTS idx_classes_batch(batch_id);

ALTER TABLE students ADD COLUMN IF NOT EXISTS batch_id INT NULL AFTER school_id;
ALTER TABLE students ADD COLUMN IF NOT EXISTS external_student_code VARCHAR(100) NULL AFTER student_code;
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_students_batch(batch_id);

ALTER TABLE teacher_classes ADD COLUMN IF NOT EXISTS batch_id INT NULL AFTER teacher_id;
ALTER TABLE teacher_classes ADD INDEX IF NOT EXISTS idx_teacher_classes_batch(batch_id);

ALTER TABLE prediction_batches ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 0 AFTER status;
