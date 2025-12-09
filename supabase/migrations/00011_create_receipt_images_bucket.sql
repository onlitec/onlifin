/*
# Create Receipt Images Storage Bucket

## Plain English Explanation
This migration creates a storage bucket for storing fiscal receipt images uploaded by users.
Users can upload receipt images to extract transaction data via OCR.

## Bucket Configuration
- **Bucket Name**: `app-7xkeeoe4bsap_receipts`
- **Public Access**: Yes (for displaying images)
- **File Size Limit**: 1 MB (enforced on frontend with auto-compression)
- **Allowed MIME Types**: image/jpeg, image/png, image/webp

## Security Policies
- Authenticated users can upload receipt images
- Anyone can view receipt images (public read access)
- Users can only delete their own receipts

## Notes
- Frontend will handle image compression before upload
- Images will be compressed to WEBP format if exceeding 1MB
- Filenames must contain only English letters and numbers
*/

-- Create storage bucket for receipt images
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'app-7xkeeoe4bsap_receipts',
  'app-7xkeeoe4bsap_receipts',
  true,
  1048576, -- 1 MB in bytes
  ARRAY['image/jpeg', 'image/png', 'image/webp', 'image/jpg']
)
ON CONFLICT (id) DO NOTHING;

-- Allow authenticated users to upload receipt images
CREATE POLICY "Authenticated users can upload receipts"
ON storage.objects
FOR INSERT
TO authenticated
WITH CHECK (bucket_id = 'app-7xkeeoe4bsap_receipts');

-- Allow public read access to receipt images
CREATE POLICY "Public read access to receipts"
ON storage.objects
FOR SELECT
TO public
USING (bucket_id = 'app-7xkeeoe4bsap_receipts');

-- Allow users to delete their own receipts
CREATE POLICY "Users can delete own receipts"
ON storage.objects
FOR DELETE
TO authenticated
USING (bucket_id = 'app-7xkeeoe4bsap_receipts' AND auth.uid()::text = (storage.foldername(name))[1]);