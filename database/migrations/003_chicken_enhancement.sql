-- Enhancement: Individual Chicken Tracking Migration
-- Add additional fields for detailed chicken tracking

-- Add new columns to chickens table
ALTER TABLE chickens ADD COLUMN size_category VARCHAR(50);
ALTER TABLE chickens ADD COLUMN current_age_days INTEGER DEFAULT 0;
ALTER TABLE chickens ADD COLUMN dispatch_date DATE;
ALTER TABLE chickens ADD COLUMN dispatch_reason VARCHAR(100);
ALTER TABLE chickens ADD COLUMN sale_price DECIMAL(8,2);
ALTER TABLE chickens ADD COLUMN buyer_name VARCHAR(100);
ALTER TABLE chickens ADD COLUMN buyer_contact VARCHAR(100);

-- Update existing records to have default values
UPDATE chickens SET 
    current_age_days = CASE 
        WHEN birth_date IS NOT NULL THEN 
            CAST((julianday('now') - julianday(birth_date)) AS INTEGER)
        ELSE 0 
    END
WHERE current_age_days = 0;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_chickens_status ON chickens(status);
CREATE INDEX IF NOT EXISTS idx_chickens_batch_id ON chickens(batch_id);
CREATE INDEX IF NOT EXISTS idx_chickens_tag_number ON chickens(tag_number);