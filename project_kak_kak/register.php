<?php

require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name =
        trim($_POST['full_name'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');


    /* ตรวจสอบข้อมูล */

    if ($full_name === '') {

        $error =
            'กรุณากรอกชื่อ-นามสกุล';

    } elseif ($phone === '') {

        $error =
            'กรุณากรอกเบอร์โทรศัพท์';

    } elseif (!preg_match('/^[0-9]{9,10}$/', $phone)) {

        $error =
            'กรุณากรอกเบอร์โทรศัพท์เป็นตัวเลข 9-10 หลัก';

    } else {

        /* ตรวจสอบเบอร์ซ้ำ */

        $check =
            $pdo->prepare(
                'SELECT id
                 FROM users
                 WHERE phone = ?'
            );

        $check->execute([$phone]);


        if ($check->fetch()) {

            $error =
                'เบอร์โทรศัพท์นี้มีสมาชิกในระบบแล้ว';

        } else {

            /* เพิ่มสมาชิก */

            $insert =
                $pdo->prepare(
                    'INSERT INTO users
                    (full_name, phone, points_balance)
                    VALUES (?, ?, 0)'
                );

            $insert->execute([
                $full_name,
                $phone
            ]);


            /* กลับไปหน้าหลัก */

            header(
                'Location: index.php?phone=' .
                urlencode($phone)
            );

            exit;

        }

    }

}

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
    สมัครสมาชิก
</title>


<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link
    href="https://fonts.googleapis.com/css2?family=Chonburi&family=Sarabun:wght@400;500;600&family=Kanit:wght@500;600&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="assets/style.css"
>

</head>


<body>

<div
    class="page"
    style="max-width:440px;"
>


    <div class="brand">

        <span class="mark">
            ระบบจัดการขยะ
        </span>

    </div>


    <h1 class="title">
        สมัครสมาชิก
    </h1>


    <p class="subtitle">
        สมัครสมาชิกเพื่อสะสมแต้มจากการขายขยะ
    </p>


    <div class="ticket">


        <?php if ($error): ?>

            <div
                class="alert-error"
                style="margin-bottom:15px;"
            >
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="section-title">
            ข้อมูลสมาชิก
        </div>


        <form method="post">


            <label>
                ชื่อ-นามสกุล
            </label>

            <input
                type="text"
                name="full_name"
                placeholder="กรอกชื่อ-นามสกุล"
                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                required
                style="margin-bottom:15px;"
            >


            <label>
                เบอร์โทรศัพท์
            </label>

            <input
                type="text"
                name="phone"
                placeholder="เช่น 0812345678"
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                maxlength="10"
                required
            >


            <p class="hint">
                ใช้เบอร์โทรศัพท์สำหรับค้นหาสมาชิก
            </p>


            <button
                type="submit"
                class="btn-primary btn-block"
                style="margin-top:20px;"
            >
                👤 สมัครสมาชิก
            </button>

        </form>

    </div>


    <div style="text-align:center;">

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← กลับหน้าหลัก
        </a>

    </div>


</div>

</body>

</html>