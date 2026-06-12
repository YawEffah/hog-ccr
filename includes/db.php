<?php
/**
 * Database Connection Layer
 * Returns a singleton PDO instance using settings from config.php
 */

require_once __DIR__ . '/config.php';

/**
 * Get the shared PDO database connection.
 * @throws PDOException if connection fails
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, never expose raw DB errors to the browser
            if (APP_ENV === 'development') {
                throw $e;
            }
            error_log('DB Connection failed: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:40px;color:#991B1B;">
                    <strong>Database connection error.</strong>
                    Please contact the system administrator.
                 </div>');
        }
    }

    return $pdo;
}
