# Database Migrations - Security Updates

## Account Lockout System

Run these SQL commands in your MySQL database to add account lockout functionality:

```sql
-- Add account lockout columns to nethera table
ALTER TABLE nethera 
ADD COLUMN failed_login_attempts INT DEFAULT 0 COMMENT 'Number of consecutive failed login attempts',
ADD COLUMN account_locked_until DATETIME NULL COMMENT 'Account locked until this timestamp',
ADD COLUMN last_failed_login DATETIME NULL COMMENT 'Timestamp of last failed login attempt';

-- Add index for faster lockout checks
ALTER TABLE nethera 
ADD INDEX idx_account_locked (account_locked_until);
```

## Security Logs Table

The security_logs table will be created automatically on first use. But you can also create it manually:

```sql
-- Create security logs table
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details TEXT,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Optional: Failed Login Attempts Log (For Forensics)

```sql
-- Create detailed failed login attempts log table
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Admin Queries

### Check Currently Locked Accounts

```sql
SELECT id_nethera, username, nama_lengkap, failed_login_attempts, account_locked_until
FROM nethera
WHERE account_locked_until IS NOT NULL AND account_locked_until > NOW();
```

### Manually Unlock an Account

```sql
-- Replace ? with the user ID
UPDATE nethera 
SET failed_login_attempts = 0, 
    account_locked_until = NULL 
WHERE id_nethera = ?;
```

### View Recent Security Events

```sql
SELECT sl.*, n.username, n.nama_lengkap 
FROM security_logs sl
LEFT JOIN nethera n ON sl.user_id = n.id_nethera
ORDER BY sl.created_at DESC
LIMIT 50;
```

### View Failed Login Attempts by User

```sql
SELECT sl.*, n.username, n.nama_lengkap 
FROM security_logs sl
LEFT JOIN nethera n ON sl.user_id = n.id_nethera
WHERE sl.event_type IN ('failed_login', 'locked_account_attempt')
ORDER BY sl.created_at DESC
LIMIT 100;
```

---

**Installation Steps:**

1. Run the ALTER TABLE commands for `nethera` table
2. Security_logs table will auto-create (or run CREATE TABLE manually)
3. Refresh your login page
4. Test with wrong password 10 times to see lockout in action

**Status:** Ready to deploy
