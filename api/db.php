<?php
/**
 * Database Connection & Initialization Helper
 */

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $connections = [
        // 1. Local MySQL
        [
            'host' => 'localhost',
            'dbname' => 'undangan',
            'username' => 'root',
            'password' => ''
        ],
        // 2. Remote MySQL (Hostinger fallback)
        [
            'host' => '153.92.15.11',
            'dbname' => 'u883909247_weddingg',
            'username' => 'u883909247_weddingg',
            'password' => 'Weddingg@88'
        ]
    ];

    foreach ($connections as $conn) {
        try {
            $dsn = "mysql:host={$conn['host']};dbname={$conn['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $conn['username'], $conn['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);

            // Ensure wedding_settings table exists
            initSettingsTable($pdo);
            return $pdo;
        } catch (Exception $e) {
            // Try next connection
            continue;
        }
    }

    return null;
}

function initSettingsTable($pdo) {
    if (!$pdo) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wedding_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value LONGTEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure users table exists with at least 1 admin user
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Check if admin user exists, if not create default
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (nama, email, password, created_at, updated_at) VALUES ('Administrator', 'admin@wedding.com', :pwd, NOW(), NOW())");
        $insert->execute([':pwd' => $defaultPassword]);
    }
}
