<?php
/**
 * Database Connection Manager (PDO)
 * EstateVista India
 * Robust, production-grade connection with transaction management & prepared statements.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionFailed = false;
    private static ?string $errorMessage = null;

    /**
     * Get single PDO database instance
     */
    public static function getConnection(): ?PDO {
        if (self::$instance === null && !self::$connectionFailed) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    (defined('\Pdo\Mysql::ATTR_INIT_COMMAND') ? \Pdo\Mysql::ATTR_INIT_COMMAND : (defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : 1002)) => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::$connectionFailed = true;
                self::$errorMessage = $e->getMessage();
                error_log("Database Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Check if database is currently connected
     */
    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    /**
     * Get error message if connection failed
     */
    public static function getErrorMessage(): ?string {
        return self::$errorMessage;
    }

    /**
     * Begin Database Transaction
     */
    public static function beginTransaction(): bool {
        $pdo = self::getConnection();
        return $pdo && !$pdo->inTransaction() ? $pdo->beginTransaction() : false;
    }

    /**
     * Commit Database Transaction
     */
    public static function commit(): bool {
        $pdo = self::getConnection();
        return $pdo && $pdo->inTransaction() ? $pdo->commit() : false;
    }

    /**
     * Rollback Database Transaction
     */
    public static function rollBack(): bool {
        $pdo = self::getConnection();
        return $pdo && $pdo->inTransaction() ? $pdo->rollBack() : false;
    }

    /**
     * Check if currently in transaction
     */
    public static function inTransaction(): bool {
        $pdo = self::getConnection();
        return $pdo ? $pdo->inTransaction() : false;
    }

    /**
     * Get Last Insert ID
     */
    public static function lastInsertId(): string|int {
        $pdo = self::getConnection();
        return $pdo ? $pdo->lastInsertId() : 0;
    }

    /**
     * Reusable Prepared Statement Execution
     */
    public static function execute(string $sql, array $params = []): ?PDOStatement {
        $pdo = self::getConnection();
        if (!$pdo) return null;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database::execute error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Fetch Single Row Helper
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::execute($sql, $params);
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    /**
     * Fetch All Rows Helper
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::execute($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetch Scalar / Column Helper
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0) {
        $stmt = self::execute($sql, $params);
        return $stmt ? $stmt->fetchColumn($column) : false;
    }
}
