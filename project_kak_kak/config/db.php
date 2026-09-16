<?php

$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_NAME = 'waste_system';
$DB_USER = 'root';
$DB_PASS = 'root';

define('POINT_RATE_BAHT_PER_POINT', 10);

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die(
        'เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}