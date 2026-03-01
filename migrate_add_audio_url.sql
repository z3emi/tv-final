-- إضافة عمود audio_url إلى جدول channels
-- بتاريخ 2026-03-01

ALTER TABLE channels ADD COLUMN audio_url TEXT DEFAULT NULL AFTER url;

-- إذا كنت تريد تشغيل هذا الملف من phpMyAdmin:
-- 1. افتح قاعدة البيانات tv_db
-- 2. اذهب إلى "SQL"
-- 3. الصق محتوى هذا الملف
-- 4. اضغط "تنفيذ" (Execute)