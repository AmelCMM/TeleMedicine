<?php
require_once __DIR__ . '/config/database.php';

$name     = 'Admin';
$email    = 'admin@mediconnect.zm';
$phone    = '+260970000000';
$password = 'Admin@2024';
$role     = 'admin';

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$db = Database::getConnection();

$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit;
}

$stmt = $db->prepare(
    'INSERT INTO users (name, email, phone, password_hash, role, is_verified, is_active)
     VALUES (?, ?, ?, ?, ?, 1, 1)'
);
$stmt->execute([$name, $email, $phone, $passwordHash, $role]);

echo "Admin user created:\n";
echo "  Email:    $email\n";
echo "  Password: $password\n";
echo "  Role:     $role\n";
