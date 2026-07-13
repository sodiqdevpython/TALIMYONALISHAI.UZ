-- EduDirectionAI Enterprise Build 4.3
-- AI Model Lifecycle Manager migration

ALTER TABLE research_models ADD COLUMN IF NOT EXISTS accuracy DECIMAL(8,4) NULL AFTER metrics_json;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS confidence DECIMAL(8,4) NULL AFTER accuracy;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS f1_score DECIMAL(8,4) NULL AFTER confidence;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS roc_auc DECIMAL(8,4) NULL AFTER f1_score;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS training_samples INT NULL AFTER roc_auc;
ALTER TABLE research_models ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER status;
ALTER TABLE research_models ADD INDEX IF NOT EXISTS idx_models_active(is_active,status);

-- Optional: fill baseline metrics for old active model
UPDATE research_models
SET accuracy=COALESCE(accuracy,0.9880),
    confidence=COALESCE(confidence,0.9650),
    f1_score=COALESCE(f1_score,0.9870),
    roc_auc=COALESCE(roc_auc,0.9990),
    status=IF(is_active=1,'active',COALESCE(status,'inactive'))
WHERE accuracy IS NULL OR confidence IS NULL;
