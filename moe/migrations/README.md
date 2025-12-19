# Database Migrations

Migration files untuk Mediterranean of Egypt - School Management System.

## How to Use

Jalankan migration files secara berurutan di database MySQL/MariaDB:

```bash
# Via MySQL CLI
mysql -u username -p database_name < 001_create_core_tables.sql
mysql -u username -p database_name < 002_create_pet_system_tables.sql
mysql -u username -p database_name < 003_create_class_management_tables.sql
mysql -u username -p database_name < 004_seed_data.sql
```

Atau import via phpMyAdmin/HeidiSQL.

## Migration Files

| File | Description |
|------|-------------|
| `001_create_core_tables.sql` | Core tables: sanctuary, nethera (users), rate_limits |
| `002_create_pet_system_tables.sql` | Pet system: species, pets, skills, shop, inventory, battles |
| `003_create_class_management_tables.sql` | Class: grades, schedules, punishments, admin logs |
| `004_seed_data.sql` | Seed data: shop items, pet species, achievements |

## Notes

- Semua tables menggunakan `InnoDB` engine
- Charset: `utf8mb4_unicode_ci`
- Foreign keys dengan `ON DELETE CASCADE`
- Run migrations in order (001 → 004)
