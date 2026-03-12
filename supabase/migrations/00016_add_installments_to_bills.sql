-- ============================================================================
-- Migration: Add installments and advanced recurrence to bills
-- ============================================================================

-- Add columns to bills_to_pay
ALTER TABLE bills_to_pay 
ADD COLUMN IF NOT EXISTS is_installment BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS installment_number INTEGER,
ADD COLUMN IF NOT EXISTS total_installments INTEGER,
ADD COLUMN IF NOT EXISTS parent_bill_id UUID REFERENCES bills_to_pay(id) ON DELETE CASCADE,
ADD COLUMN IF NOT EXISTS next_occurrence_generated BOOLEAN DEFAULT false;

-- Add columns to bills_to_receive
ALTER TABLE bills_to_receive 
ADD COLUMN IF NOT EXISTS is_installment BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS installment_number INTEGER,
ADD COLUMN IF NOT EXISTS total_installments INTEGER,
ADD COLUMN IF NOT EXISTS parent_bill_id UUID REFERENCES bills_to_receive(id) ON DELETE CASCADE,
ADD COLUMN IF NOT EXISTS next_occurrence_generated BOOLEAN DEFAULT false;

-- Add indexes for installments
CREATE INDEX IF NOT EXISTS idx_bills_to_pay_parent_bill_id ON bills_to_pay(parent_bill_id);
CREATE INDEX IF NOT EXISTS idx_bills_to_receive_parent_bill_id ON bills_to_receive(parent_bill_id);

-- Update RLS policies (usually unnecessary if only adding columns, but good to check)
-- No changes needed to existing policies as they use user_id/company_id which are already present.

-- Comments
COMMENT ON COLUMN bills_to_pay.is_installment IS 'Indicates if this bill is part of a series of installments';
COMMENT ON COLUMN bills_to_pay.installment_number IS 'Current installment number (e.g., 1)';
COMMENT ON COLUMN bills_to_pay.total_installments IS 'Total number of installments (e.g., 12)';
COMMENT ON COLUMN bills_to_pay.parent_bill_id IS 'References the first bill of the series (either recurring or installments)';
COMMENT ON COLUMN bills_to_pay.next_occurrence_generated IS 'Flag to prevent multiple generation of the next recurring bill';

COMMENT ON COLUMN bills_to_receive.is_installment IS 'Indicates if this bill is part of a series of installments';
COMMENT ON COLUMN bills_to_receive.installment_number IS 'Current installment number (e.g., 1)';
COMMENT ON COLUMN bills_to_receive.total_installments IS 'Total number of installments (e.g., 12)';
COMMENT ON COLUMN bills_to_receive.parent_bill_id IS 'References the first bill of the series (either recurring or installments)';
COMMENT ON COLUMN bills_to_receive.next_occurrence_generated IS 'Flag to prevent multiple generation of the next recurring bill';
