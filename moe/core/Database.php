<?php
/**
 * Database Wrapper Class
 * Mediterranean of Egypt - School Management System
 * 
 * Provides a simple, clean interface for database operations.
 * Compatible with cPanel shared hosting (no PDO requirements).
 * 
 * Usage:
 *   $users = DB::query("SELECT * FROM nethera WHERE id_nethera = ?", [$id]);
 *   $user = DB::queryOne("SELECT * FROM nethera WHERE username = ?", [$username]);
 *   DB::insert('nethera', ['username' => 'test', 'email' => 'test@mail.com']);
 *   DB::update('nethera', ['gold' => 1000], "id_nethera = ?", [$id]);
 *   DB::delete('nethera', "id_nethera = ?", [$id]);
 */

class DB
{
    private static $conn = null;

    /**
     * Initialize database connection
     * @param mysqli $connection Existing mysqli connection
     */
    public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Get the database connection
     * @return mysqli
     */
    public static function getConnection()
    {
        if (self::$conn === null) {
            throw new Exception("Database not initialized. Call DB::init() first.");
        }
        return self::$conn;
    }

    /**
     * Execute a SELECT query and return all results
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array Array of associative arrays
     * 
     * @example
     *   $users = DB::query("SELECT * FROM nethera WHERE status_akun = ?", ['Aktif']);
     */
    public static function query($sql, $params = [])
    {
        $stmt = self::prepareAndBind($sql, $params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $rows;
    }

    /**
     * Execute a SELECT query and return first row only
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return array|null Associative array or null if not found
     * 
     * @example
     *   $user = DB::queryOne("SELECT * FROM nethera WHERE id_nethera = ?", [$id]);
     */
    public static function queryOne($sql, $params = [])
    {
        $rows = self::query($sql, $params);
        return $rows[0] ?? null;
    }

    /**
     * Execute a SELECT query and return single value
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return mixed Single value or null
     * 
     * @example
     *   $count = DB::queryValue("SELECT COUNT(*) FROM nethera WHERE status_akun = ?", ['Aktif']);
     */
    public static function queryValue($sql, $params = [])
    {
        $row = self::queryOne($sql, $params);
        return $row ? reset($row) : null;
    }

    /**
     * Insert a new row
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Insert ID on success, false on failure
     * 
     * @example
     *   $id = DB::insert('user_pets', ['user_id' => 1, 'species_id' => 5, 'level' => 1]);
     */
    public static function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::prepareAndBind($sql, array_values($data));

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id(self::$conn);
            mysqli_stmt_close($stmt);
            return $id;
        }

        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Update existing rows
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param string $where WHERE clause with ? placeholders
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     * 
     * @example
     *   DB::update('nethera', ['gold' => 1500], "id_nethera = ?", [$userId]);
     */
    public static function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge(array_values($data), $whereParams);

        $stmt = self::prepareAndBind($sql, $params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows(self::$conn);
        mysqli_stmt_close($stmt);

        return $affected;
    }

    /**
     * Delete rows
     * 
     * @param string $table Table name
     * @param string $where WHERE clause with ? placeholders
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     * 
     * @example
     *   DB::delete('rate_limits', "identifier = ? AND action = ?", [$userId, 'login']);
     */
    public static function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = self::prepareAndBind($sql, $params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows(self::$conn);
        mysqli_stmt_close($stmt);

        return $affected;
    }

    /**
     * Execute raw SQL (for complex queries, transactions, etc.)
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return bool Success status
     * 
     * @example
     *   DB::execute("UPDATE nethera SET gold = gold + ? WHERE id_nethera = ?", [100, $userId]);
     */
    public static function execute($sql, $params = [])
    {
        $stmt = self::prepareAndBind($sql, $params);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    /**
     * Get last insert ID
     * @return int
     */
    public static function lastInsertId()
    {
        return mysqli_insert_id(self::$conn);
    }

    /**
     * Get number of affected rows from last query
     * @return int
     */
    public static function affectedRows()
    {
        return mysqli_affected_rows(self::$conn);
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction()
    {
        mysqli_begin_transaction(self::$conn);
    }

    /**
     * Commit a transaction
     */
    public static function commit()
    {
        mysqli_commit(self::$conn);
    }

    /**
     * Rollback a transaction
     */
    public static function rollback()
    {
        mysqli_rollback(self::$conn);
    }

    /**
     * Escape string (use sparingly - prefer prepared statements)
     * @param string $string
     * @return string
     */
    public static function escape($string)
    {
        return mysqli_real_escape_string(self::$conn, $string);
    }

    /**
     * Private: Prepare statement and bind parameters
     */
    private static function prepareAndBind($sql, $params)
    {
        $stmt = mysqli_prepare(self::$conn, $sql);

        if (!$stmt) {
            throw new Exception("Query prepare failed: " . mysqli_error(self::$conn) . " | SQL: $sql");
        }

        if (!empty($params)) {
            $types = self::getParamTypes($params);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        return $stmt;
    }

    /**
     * Private: Determine parameter types for binding
     */
    private static function getParamTypes($params)
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_null($param)) {
                $types .= 's'; // NULL as string works in mysqli
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}
