ALTER TABLE properties ADD COLUMN IF NOT EXISTS main_image_path VARCHAR(255) DEFAULT NULL;
ALTER TABLE properties ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE property_images ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE property_images ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_properties_status ON properties(status);
CREATE INDEX IF NOT EXISTS idx_properties_city ON properties(city);
CREATE INDEX IF NOT EXISTS idx_properties_deleted ON properties(deleted_at);
CREATE INDEX IF NOT EXISTS idx_properties_main_image ON properties(main_image_path);
CREATE INDEX IF NOT EXISTS idx_property_images_deleted ON property_images(deleted_at);
CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);
CREATE INDEX IF NOT EXISTS idx_leads_deleted ON leads(deleted_at);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
