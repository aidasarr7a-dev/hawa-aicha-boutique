<?php
/**
 * Connexion PDO (singleton)
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = defined('DB_DSN')
            ? DB_DSN
            : sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Connexion à la base de données impossible. Vérifiez le fichier config.php.');
        }
    }
    return $pdo;
}
