<?php

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function calc_item_amount(
    float $weightKg,
    float $unitPrice
): float {
    return round($weightKg * $unitPrice, 2);
}

function calc_points_from_amount(
    float $totalAmount
): int {
    return (int)floor(
        $totalAmount / POINT_RATE_BAHT_PER_POINT
    );
}

function get_active_categories(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, name, unit_price
         FROM waste_categories
         WHERE is_active = 1
         ORDER BY name'
    );

    return $stmt->fetchAll();
}

function find_user_by_phone(
    PDO $pdo,
    string $phone
): ?array {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, phone, points_balance
         FROM users
         WHERE phone = ?
         LIMIT 1'
    );

    $stmt->execute([$phone]);

    $user = $stmt->fetch();

    return $user ?: null;
}