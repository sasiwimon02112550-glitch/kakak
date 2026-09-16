<?php

require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

/* ======================================================
   เพิ่มประเภทขยะ
====================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add_category'
) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['unit_price'] ?? 0);

    if ($name === '' || $price <= 0) {
        $error = 'กรุณากรอกชื่อและราคาขยะให้ถูกต้อง';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO waste_categories
            (name, unit_price, is_active)
            VALUES (?, ?, 1)'
        );

        $stmt->execute([
            $name,
            $price
        ]);

        $message = 'เพิ่มประเภทขยะเรียบร้อยแล้ว';
    }
}


/* ======================================================
   แก้ไขประเภทขยะ
====================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'edit_category'
) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['unit_price'] ?? 0);

    if ($id <= 0 || $name === '' || $price <= 0) {
        $error = 'กรุณากรอกข้อมูลประเภทขยะให้ถูกต้อง';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE waste_categories
             SET name = ?, unit_price = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $name,
            $price,
            $id
        ]);

        $message = 'แก้ไขราคาขยะเรียบร้อยแล้ว';
    }
}


/* ======================================================
   เพิ่มของรางวัล
====================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add_reward'
) {
    $name = trim($_POST['reward_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $points = (int)($_POST['points_required'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if (
        $name === '' ||
        $points <= 0 ||
        $stock < 0
    ) {
        $error = 'กรุณากรอกข้อมูลของรางวัลให้ถูกต้อง';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO rewards
            (
                reward_name,
                description,
                points_required,
                stock,
                is_active
            )
            VALUES (?, ?, ?, ?, 1)'
        );

        $stmt->execute([
            $name,
            $description,
            $points,
            $stock
        ]);

        $message = 'เพิ่มของรางวัลเรียบร้อยแล้ว';
    }
}


/* ======================================================
   แก้ไขของรางวัล
====================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'edit_reward'
) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['reward_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $points = (int)($_POST['points_required'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if (
        $id <= 0 ||
        $name === '' ||
        $points <= 0 ||
        $stock < 0
    ) {
        $error = 'กรุณากรอกข้อมูลของรางวัลให้ถูกต้อง';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE rewards
             SET reward_name = ?,
                 description = ?,
                 points_required = ?,
                 stock = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $name,
            $description,
            $points,
            $stock,
            $id
        ]);

        $message = 'แก้ไขรางวัลเรียบร้อยแล้ว';
    }
}


/* ======================================================
   เปิด / ปิดประเภทขยะ
====================================================== */

if (isset($_GET['toggle_category'])) {

    $id = (int)$_GET['toggle_category'];

    $stmt = $pdo->prepare(
        'UPDATE waste_categories
         SET is_active =
             IF(is_active = 1, 0, 1)
         WHERE id = ?'
    );

    $stmt->execute([$id]);

    header('Location: admin.php');
    exit;
}


/* ======================================================
   เปิด / ปิดของรางวัล
====================================================== */

if (isset($_GET['toggle_reward'])) {

    $id = (int)$_GET['toggle_reward'];

    $stmt = $pdo->prepare(
        'UPDATE rewards
         SET is_active =
             IF(is_active = 1, 0, 1)
         WHERE id = ?'
    );

    $stmt->execute([$id]);

    header('Location: admin.php');
    exit;
}


/* ======================================================
   Dashboard
====================================================== */

$totalUsers = (int)$pdo
    ->query('SELECT COUNT(*) FROM users')
    ->fetchColumn();

$totalTransactions = (int)$pdo
    ->query('SELECT COUNT(*) FROM transactions')
    ->fetchColumn();

$totalAmount = (float)$pdo
    ->query(
        'SELECT COALESCE(SUM(total_amount),0)
         FROM transactions'
    )
    ->fetchColumn();

$totalPoints = (int)$pdo
    ->query(
        'SELECT COALESCE(SUM(points_balance),0)
         FROM users'
    )
    ->fetchColumn();


/* ======================================================
   ประเภทขยะ
====================================================== */

$categories = $pdo
    ->query(
        'SELECT *
         FROM waste_categories
         ORDER BY id DESC'
    )
    ->fetchAll();


/* ======================================================
   ของรางวัล
====================================================== */

$rewards = $pdo
    ->query(
        'SELECT *
         FROM rewards
         ORDER BY id DESC'
    )
    ->fetchAll();


/* ======================================================
   รายการรับซื้อ 20 รายการล่าสุด
====================================================== */

$transactions = $pdo
    ->query(
        'SELECT
            t.id,
            t.total_amount,
            t.total_points,
            t.created_at,
            u.full_name,
            u.phone
         FROM transactions t
         INNER JOIN users u
            ON u.id = t.user_id
         ORDER BY t.id DESC
         LIMIT 20'
    )
    ->fetchAll();

?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
    Admin - ระบบจัดการขยะ
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="assets/style.css"
>

<style>

.admin-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:18px;
    margin-bottom:25px;
}

.admin-table{
    width:100%;
    border-collapse:collapse;
}

.admin-table th,
.admin-table td{
    padding:12px 10px;
    text-align:left;
    border-bottom:1px solid #e1eee4;
}

.admin-table th{
    color:#315a43;
    background:#edf8f0;
}

.edit-box{
    margin-top:10px;
    padding:15px;

    border:1px solid #d4e8da;
    border-radius:14px;

    background:#f7fcf8;
}

.edit-box input{
    margin-bottom:8px;
}

.edit-grid{
    display:grid;
    grid-template-columns:2fr 1fr auto;
    gap:10px;
    align-items:end;
}

.reward-edit-grid{
    display:grid;
    grid-template-columns:2fr 2fr 1fr 1fr auto;
    gap:10px;
    align-items:end;
}

.small-btn{
    min-height:40px;
    padding:8px 12px;
    font-size:13px;
}

.status-on{
    color:#159447;
    font-weight:600;
}

.status-off{
    color:#df5947;
    font-weight:600;
}

@media(max-width:900px){

    .admin-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .edit-grid,
    .reward-edit-grid{
        grid-template-columns:1fr;
    }

}

@media(max-width:600px){

    .admin-grid{
        grid-template-columns:1fr;
    }

}

</style>

</head>


<body>

<div
    class="page"
    style="max-width:1250px;"
>

<div class="brand">

    <span class="mark">
        ADMIN
    </span>

</div>

<h1 class="title">
    จัดการระบบ
</h1>

<p class="subtitle">
    ระบบบริหารจัดการขยะและแลกแต้มรางวัล
</p>


<!-- ==================================================
     Dashboard
================================================== -->

<div class="admin-grid">

    <div class="stat">

        <div class="stat-label">
            สมาชิกทั้งหมด
        </div>

        <div class="stat-value">
            <?= number_format($totalUsers) ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-label">
            รายการรับซื้อ
        </div>

        <div class="stat-value">
            <?= number_format($totalTransactions) ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-label">
            ยอดรับซื้อรวม
        </div>

        <div class="stat-value">
            <?= number_format($totalAmount, 2) ?>
        </div>

    </div>


    <div class="stat">

        <div class="stat-label">
            แต้มที่สมาชิกถืออยู่
        </div>

        <div class="stat-value">
            <?= number_format($totalPoints) ?>
        </div>

    </div>

</div>


<!-- MESSAGE -->

<?php if ($message): ?>

<div class="alert-success">
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert-error">
    <?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<!-- ==================================================
     จัดการราคาขยะ
================================================== -->

<div class="ticket">

    <div class="section-title">
        ♻️ จัดการราคาขยะ
    </div>


    <!-- เพิ่มประเภทขยะ -->

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="add_category"
        >

        <div class="edit-grid">

            <div>

                <label>
                    ชื่อประเภทขยะ
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="เช่น กระดาษ"
                    required
                >

            </div>


            <div>

                <label>
                    ราคา / กก.
                </label>

                <input
                    type="number"
                    name="unit_price"
                    step="0.01"
                    min="0.01"
                    placeholder="ราคา"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                + เพิ่มขยะ
            </button>

        </div>

    </form>


    <!-- ตารางราคาขยะ -->

    <div
        class="table-wrap"
        style="margin-top:20px;"
    >

        <table class="admin-table">

            <thead>

            <tr>

                <th>
                    ชื่อขยะ
                </th>

                <th>
                    ราคา / กก.
                </th>

                <th>
                    สถานะ
                </th>

                <th>
                    จัดการ
                </th>

            </tr>

            </thead>


            <tbody>

            <?php foreach ($categories as $category): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($category['name']) ?>
                </td>


                <td>

                    <?= number_format(
                        (float)$category['unit_price'],
                        2
                    ) ?>

                    บาท

                </td>


                <td>

                    <?php if ($category['is_active']): ?>

                        <span class="status-on">
                            เปิดใช้งาน
                        </span>

                    <?php else: ?>

                        <span class="status-off">
                            ปิดใช้งาน
                        </span>

                    <?php endif; ?>

                </td>


                <td>

                    <a
                        href="admin.php?toggle_category=<?= (int)$category['id'] ?>"
                        class="btn btn-secondary small-btn"
                    >
                        <?= $category['is_active']
                            ? 'ปิด'
                            : 'เปิด'
                        ?>
                    </a>

                </td>

            </tr>


            <!-- แบบฟอร์มแก้ไข -->

            <tr>

                <td colspan="4">

                    <div class="edit-box">

                        <strong>
                            ✏️ แก้ไข <?= htmlspecialchars($category['name']) ?>
                        </strong>


                        <form
                            method="post"
                            style="margin-top:12px;"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="edit_category"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$category['id'] ?>"
                            >


                            <div class="edit-grid">

                                <div>

                                    <label>
                                        ชื่อประเภทขยะ
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        value="<?= htmlspecialchars($category['name']) ?>"
                                        required
                                    >

                                </div>


                                <div>

                                    <label>
                                        ราคา / กก.
                                    </label>

                                    <input
                                        type="number"
                                        name="unit_price"
                                        value="<?= htmlspecialchars($category['unit_price']) ?>"
                                        step="0.01"
                                        min="0.01"
                                        required
                                    >

                                </div>


                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    💾 บันทึก
                                </button>

                            </div>

                        </form>

                    </div>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- ==================================================
     จัดการรางวัล
================================================== -->

<div class="ticket">

    <div class="section-title">
        🎁 จัดการรางวัล
    </div>


    <!-- เพิ่มรางวัล -->

    <form method="post">

        <input
            type="hidden"
            name="action"
            value="add_reward"
        >


        <div class="form-grid">

            <div>

                <label>
                    ชื่อรางวัล
                </label>

                <input
                    type="text"
                    name="reward_name"
                    placeholder="เช่น ถุงผ้า"
                    required
                >

            </div>


            <div>

                <label>
                    รายละเอียด
                </label>

                <input
                    type="text"
                    name="description"
                    placeholder="รายละเอียดรางวัล"
                >

            </div>

        </div>


        <div class="form-grid">

            <div>

                <label>
                    แต้มที่ใช้
                </label>

                <input
                    type="number"
                    name="points_required"
                    min="1"
                    placeholder="เช่น 100"
                    required
                >

            </div>


            <div>

                <label>
                    จำนวน
                </label>

                <input
                    type="number"
                    name="stock"
                    min="0"
                    placeholder="เช่น 10"
                    required
                >

            </div>

        </div>


        <button
            type="submit"
            class="btn btn-primary"
        >
            + เพิ่มรางวัล
        </button>

    </form>


    <!-- ตารางรางวัล -->

    <div
        class="table-wrap"
        style="margin-top:20px;"
    >

        <table class="admin-table">

            <thead>

            <tr>

                <th>
                    รางวัล
                </th>

                <th>
                    รายละเอียด
                </th>

                <th>
                    แต้ม
                </th>

                <th>
                    จำนวน
                </th>

                <th>
                    สถานะ
                </th>

                <th>
                    จัดการ
                </th>

            </tr>

            </thead>


            <tbody>

            <?php foreach ($rewards as $reward): ?>

            <tr>

                <td>
                    <?= htmlspecialchars($reward['reward_name']) ?>
                </td>


                <td>
                    <?= htmlspecialchars($reward['description'] ?? '') ?>
                </td>


                <td>

                    <?= number_format(
                        (int)$reward['points_required']
                    ) ?>

                    แต้ม

                </td>


                <td>
                    <?= number_format(
                        (int)$reward['stock']
                    ) ?>
                </td>


                <td>

                    <?php if ($reward['is_active']): ?>

                        <span class="status-on">
                            เปิดใช้งาน
                        </span>

                    <?php else: ?>

                        <span class="status-off">
                            ปิดใช้งาน
                        </span>

                    <?php endif; ?>

                </td>


                <td>

                    <a
                        href="admin.php?toggle_reward=<?= (int)$reward['id'] ?>"
                        class="btn btn-secondary small-btn"
                    >
                        <?= $reward['is_active']
                            ? 'ปิด'
                            : 'เปิด'
                        ?>
                    </a>

                </td>

            </tr>


            <!-- แบบฟอร์มแก้ไขรางวัล -->

            <tr>

                <td colspan="6">

                    <div class="edit-box">

                        <strong>
                            ✏️ แก้ไขรางวัล
                        </strong>


                        <form
                            method="post"
                            style="margin-top:12px;"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="edit_reward"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$reward['id'] ?>"
                            >


                            <div class="form-grid">

                                <div>

                                    <label>
                                        ชื่อรางวัล
                                    </label>

                                    <input
                                        type="text"
                                        name="reward_name"
                                        value="<?= htmlspecialchars($reward['reward_name']) ?>"
                                        required
                                    >

                                </div>


                                <div>

                                    <label>
                                        รายละเอียด
                                    </label>

                                    <input
                                        type="text"
                                        name="description"
                                        value="<?= htmlspecialchars($reward['description'] ?? '') ?>"
                                    >

                                </div>

                            </div>


                            <div class="form-grid">

                                <div>

                                    <label>
                                        แต้มที่ใช้
                                    </label>

                                    <input
                                        type="number"
                                        name="points_required"
                                        value="<?= (int)$reward['points_required'] ?>"
                                        min="1"
                                        required
                                    >

                                </div>


                                <div>

                                    <label>
                                        จำนวนสินค้า
                                    </label>

                                    <input
                                        type="number"
                                        name="stock"
                                        value="<?= (int)$reward['stock'] ?>"
                                        min="0"
                                        required
                                    >

                                </div>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                💾 บันทึกรางวัล
                            </button>

                        </form>

                    </div>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- ==================================================
     รายการรับซื้อ
================================================== -->

<div class="ticket">

    <div class="section-title">
        📋 รายการรับซื้อ 20 รายการล่าสุด
    </div>


    <div class="table-wrap">

        <table class="admin-table">

            <thead>

            <tr>

                <th>
                    #
                </th>

                <th>
                    สมาชิก
                </th>

                <th>
                    เบอร์
                </th>

                <th>
                    ยอดเงิน
                </th>

                <th>
                    แต้ม
                </th>

                <th>
                    วันที่
                </th>

                <th>
                    ใบเสร็จ
                </th>

            </tr>

            </thead>


            <tbody>

            <?php if (!$transactions): ?>

            <tr>

                <td
                    colspan="7"
                    style="text-align:center;padding:30px;"
                >
                    ยังไม่มีรายการรับซื้อ
                </td>

            </tr>

            <?php endif; ?>


            <?php foreach ($transactions as $tx): ?>

            <tr>

                <td>
                    <?= (int)$tx['id'] ?>
                </td>


                <td>
                    <?= htmlspecialchars($tx['full_name']) ?>
                </td>


                <td>
                    <?= htmlspecialchars($tx['phone']) ?>
                </td>


                <td>

                    <?= number_format(
                        (float)$tx['total_amount'],
                        2
                    ) ?>

                    บาท

                </td>


                <td>

                    <?= number_format(
                        (int)$tx['total_points']
                    ) ?>

                    แต้ม

                </td>


                <td>

                    <?= date(
                        'd/m/Y H:i',
                        strtotime($tx['created_at'])
                    ) ?>

                </td>


                <td>

                    <a
                        href="receipt.php?id=<?= (int)$tx['id'] ?>"
                        class="btn btn-secondary small-btn"
                    >
                        ดู
                    </a>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- ==================================================
     เมนู
================================================== -->

<div
    style="
        text-align:center;
        margin:25px 0;
    "
>

    <a
        href="index.php"
        class="btn btn-primary"
    >
        ← กลับหน้าหลัก
    </a>


    <a
        href="rewards.php"
        class="btn btn-secondary"
    >
        🎁 หน้ารางวัล
    </a>

</div>


</div>

</body>

</html>