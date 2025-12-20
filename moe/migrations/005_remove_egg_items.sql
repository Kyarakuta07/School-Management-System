-- =====================================================
-- Migration: Remove egg items from shop
-- Description: Remove Bronze Egg, Silver Egg, Golden Egg from shop_items
-- Run this on your database to remove the egg items
-- =====================================================

-- Remove egg items from shop (if they exist)
DELETE FROM shop_items WHERE name LIKE '%Bronze Egg%';
DELETE FROM shop_items WHERE name LIKE '%Silver Egg%';
DELETE FROM shop_items WHERE name LIKE '%Golden Egg%';

-- Alternative: If you want to just disable them instead of deleting
-- UPDATE shop_items SET is_available = 0, is_purchasable = 0 WHERE name LIKE '%Egg%';
