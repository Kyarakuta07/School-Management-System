-- =====================================================
-- Migration: 008_sanctuary_lore.sql
-- Description: Add detailed lore descriptions for each sanctuary
-- Mediterranean of Egypt - School Management System
-- =====================================================

-- Update Ammit Sanctuary Lore
UPDATE sanctuary SET deskripsi = 'Sanctuary Ammit, the fourth sanctuary "Sanctu #4" was forged for Nethera, bearer of Ammit''s divine blood. It shelters children chosen for their sense of justice, clarity of judgment, iron strong hearts, and wandering spirits destined for greater paths. In the myths of ancient Kemet, Ammit is the Devourer of Death: a fearsome being with the crocodile''s jaws, the lion''s strength, and the hippopotamus''s unyielding might. No wicked soul escapes her shadow.' 
WHERE nama_sanctuary = 'Ammit';

-- Update Khonsu Sanctuary Lore
UPDATE sanctuary SET deskripsi = 'Sanctuary Khonsu, blessed by the Moon God who traverses the night sky. Its children are guided by lunar wisdom, mastering the flow of time and natural cycles. They are dreamers, healers of the wounded soul, and navigators of destiny''s darkest hours. Under Khonsu''s silver light, they find clarity in chaos and hope in shadow.' 
WHERE nama_sanctuary = 'Khonsu';

-- Update Hathor Sanctuary Lore
UPDATE sanctuary SET deskripsi = 'Sanctuary Hathor, House of Love and Beauty, blessed by the goddess of joy and motherhood. Its members embody grace, creativity, and harmony in all things. They are artists who paint with emotion, peacekeepers who mend broken bonds, and keepers of celebration. Where Hathor''s children walk, music follows and hearts are lifted.' 
WHERE nama_sanctuary = 'Hathor';

-- Update Osiris Sanctuary Lore
UPDATE sanctuary SET deskripsi = 'Sanctuary Osiris, guardian of the sacred Underworld and lord of resurrection. Its children understand the profound mysteries of transformation, rebirth, and the eternal balance between life and death. They are philosophers of the soul, guides for the lost, and protectors of ancient secrets. Through death, they teach the meaning of life.' 
WHERE nama_sanctuary = 'Osiris';

-- Update Horus Sanctuary Lore
UPDATE sanctuary SET deskripsi = 'Sanctuary Horus, House of the Sky God, the falcon-headed avenger. Its members soar above the ordinary, blessed with keen vision and unwavering focus. They are protectors of the weak, seekers of vengeance against injustice, and rightful rulers of their own destiny. With the Eye of Horus watching over them, they see truth where others see only shadow.' 
WHERE nama_sanctuary = 'Horus';

-- Verify updates
-- SELECT id_sanctuary, nama_sanctuary, LEFT(deskripsi, 50) as deskripsi_preview FROM sanctuary;

