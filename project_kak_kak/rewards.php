<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$phone = trim($_GET['phone'] ?? '');
$success = isset($_GET['success']);
$member = null;
$error = '';

if ($phone !== '') {
    $member = find_user_by_phone($pdo, $phone);
    if (!$member) {
        $error = 'ไม่พบสมาชิกจากเบอร์โทรนี้';
    }
}

$rewards = $pdo->query(
    'SELECT id, reward_name, description, points_required, stock, is_active
     FROM rewards
     WHERE is_active = 1 AND stock > 0
     ORDER BY points_required ASC, reward_name ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>แลกแต้มรางวัล</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Chonburi&family=Sarabun:wght@400;500;600&family=Kanit:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
.reward-card { transition: .25s; }
.reward-card:hover { transform: translateY(-5px); }
.reward-card .points { font-size: 24px; font-weight: 700; margin: 10px 0; }
.btn-block { width: 100%; }
</style>
</head>
<body>
<div class="page narrow">

    <div class="brand">
        <span class="mark">♻️ ศูนย์รับซื้อขยะรีไซเคิล</span>
    </div>

    <h1 class="title">แลกแต้มรางวัล 🎁</h1>
    <p class="subtitle">ใช้แต้มสะสมแลกรางวัลที่ต้องการ</p>

    <div class="nav">
        <a href="index.php">หน้าชั่งขยะ</a>
        <a href="register.php">สมัครสมาชิก</a>
        <a href="admin.php">Admin</a>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">
            🎉 แลกรางวัลสำเร็จ! กรุณาติดต่อเจ้าหน้าที่เพื่อรับรางวัล
        </div>
    <?php endif; ?>

    <div class="ticket">
        <div class="section-title">🔎 ค้นหาสมาชิก</div>
        <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
                <label>เบอร์โทรศัพท์</label>
                <input type="text" name="phone" value="<?= e($phone) ?>" placeholder="เช่น 0812345678" required>
            </div>
            <button class="btn-secondary" type="submit">ค้นหา</button>
        </form>

        <?php if ($error): ?>
            <p class="alert-error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($member): ?>
            <div class="member-info">
                👤 สมาชิก <b><?= e($member['full_name']) ?></b>
                — ⭐ มี <b><?= number_format((int)$member['points_balance']) ?></b> แต้ม
            </div>
        <?php endif; ?>
    </div>

    <?php if ($member): ?>
        <div class="ticket">
            <div class="section-title">🎁 รางวัลที่สามารถแลกได้</div>

            <?php foreach ($rewards as $r): ?>
                <div class="reward-card">
                    <h3><?= e($r['reward_name']) ?></h3>
                    <div style="color:var(--color-muted);font-size:13px">
                        <?= e($r['description']) ?>
                    </div>

                    <div class="points">
                        ⭐ <?= number_format((int)$r['points_required']) ?> แต้ม
                    </div>

                    <div style="font-size:12px;color:var(--color-muted);margin:5px 0 10px">
                        เหลือ <?= number_format((int)$r['stock']) ?> ชิ้น
                    </div>

                    <?php if ((int)$member['points_balance'] >= (int)$r['points_required']): ?>
                        <form method="post" action="redeem.php" onsubmit="return confirm('ยืนยันการแลกรางวัลนี้หรือไม่?');">
                            <input type="hidden" name="user_id" value="<?= (int)$member['id'] ?>">
                            <input type="hidden" name="reward_id" value="<?= (int)$r['id'] ?>">
                            <button class="btn-primary btn-block" type="submit">🎁 แลกรางวัลนี้</button>
                        </form>
                    <?php else: ?>
                        <button class="btn-secondary btn-block" disabled>แต้มไม่เพียงพอ</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$rewards): ?>
                <p class="hint">ยังไม่มีรางวัลที่เปิดให้แลก หรือรางวัลหมดแล้ว</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
