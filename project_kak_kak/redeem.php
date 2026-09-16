<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rewards.php');
    exit;
}

$userId   = (int)($_POST['user_id'] ?? 0);
$rewardId = (int)($_POST['reward_id'] ?? 0);

if ($userId <= 0 || $rewardId <= 0) {
    die('ข้อมูลสำหรับแลกรางวัลไม่ถูกต้อง');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, full_name, phone, points_balance
         FROM users
         WHERE id = ?
         FOR UPDATE'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('ไม่พบสมาชิก');
    }

    // ใช้ reward_name ให้ตรงกับฐานข้อมูลปัจจุบัน
    $stmt = $pdo->prepare(
        'SELECT id, reward_name, description, points_required, stock
         FROM rewards
         WHERE id = ? AND is_active = 1
         FOR UPDATE'
    );
    $stmt->execute([$rewardId]);
    $reward = $stmt->fetch();

    if (!$reward) {
        throw new Exception('ไม่พบของรางวัล หรือของรางวัลถูกปิดใช้งาน');
    }

    $requiredPoints = (int)$reward['points_required'];
    $stock = (int)$reward['stock'];
    $balance = (int)$user['points_balance'];

    if ($stock <= 0) {
        throw new Exception('ของรางวัลนี้หมดแล้ว');
    }

    if ($balance < $requiredPoints) {
        throw new Exception('แต้มของคุณไม่เพียงพอ');
    }

    // หักแต้ม
    $stmt = $pdo->prepare(
        'UPDATE users
         SET points_balance = points_balance - ?
         WHERE id = ?'
    );
    $stmt->execute([$requiredPoints, $userId]);

    // ลดจำนวนของรางวัล
    $stmt = $pdo->prepare(
        'UPDATE rewards
         SET stock = stock - 1
         WHERE id = ? AND stock > 0'
    );
    $stmt->execute([$rewardId]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('ไม่สามารถตัดสต็อกของรางวัลได้');
    }

    // บันทึกการแลก
    $stmt = $pdo->prepare(
        'INSERT INTO redemptions
         (user_id, reward_id, points_used, status)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $rewardId,
        $requiredPoints,
        'รอรับรางวัล'
    ]);

    $redemptionId = (int)$pdo->lastInsertId();

    // บันทึกประวัติแต้ม
    $stmt = $pdo->prepare(
        'INSERT INTO point_history
         (user_id, points, reason, ref_transaction_id)
         VALUES (?, ?, ?, NULL)'
    );
    $stmt->execute([
        $userId,
        -$requiredPoints,
        'แลกรางวัล: ' . $reward['reward_name'] . ' #' . $redemptionId
    ]);

    $pdo->commit();

    header(
        'Location: rewards.php?phone=' . urlencode($user['phone']) . '&success=1'
    );
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die('แลกรางวัลไม่สำเร็จ: ' . e($e->getMessage()));
}
