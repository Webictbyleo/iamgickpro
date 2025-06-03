<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Create PDO connection
try {
    $dsn = "mysql:host=127.0.0.1;dbname=iamgickpro_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'iamgickpro_user', 'iamgickpro_password123!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Connected to database successfully!\n";

    // Check if is_verified column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    $hasIsVerified = $stmt->rowCount() > 0;
    
    if (!$hasIsVerified) {
        echo "Adding is_verified column to users table...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0");
        echo "Added is_verified column successfully!\n";
    } else {
        echo "is_verified column already exists.\n";
    }

    // Update test user to be verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, email_verified = 1 WHERE email = ?");
    $stmt->execute(['test@example.com']);
    
    echo "Updated test user verification status.\n";
    
    // Check the user record
    $stmt = $pdo->prepare("SELECT id, email, email_verified, is_verified, roles FROM users WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Test user found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Email Verified: " . ($user['email_verified'] ? 'true' : 'false') . "\n";
        echo "Is Verified: " . ($user['is_verified'] ? 'true' : 'false') . "\n";
        echo "Roles: " . $user['roles'] . "\n";
    } else {
        echo "Test user not found!\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
