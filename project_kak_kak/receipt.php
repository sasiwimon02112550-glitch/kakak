<?php

require_once __DIR__ . '/config/db.php';


/* =========================
   รับ ID ใบเสร็จ
========================= */

$transaction_id =
    (int)($_GET['id'] ?? 0);


if ($transaction_id <= 0) {

    die(
        'ไม่พบรายการที่ต้องการ'
    );

}


/* =========================
   ดึงข้อมูลหัวบิล
========================= */

$stmt =
    $pdo->prepare(
        'SELECT
            t.id,
            t.total_amount,
            t.total_points,
            t.created_at,

            u.full_name,
            u.phone,
            u.points_balance

         FROM transactions t

         INNER JOIN users u
            ON u.id = t.user_id

         WHERE t.id = ?'
    );


$stmt->execute([
    $transaction_id
]);


$tx =
    $stmt->fetch();


if (!$tx) {

    die(
        'ไม่พบบิลนี้ในระบบ'
    );

}


/* =========================
   ดึงรายการขยะ
========================= */

$itemStmt =
    $pdo->prepare(
        'SELECT
            ti.weight_kg,
            ti.unit_price,
            ti.amount,

            wc.name AS category_name

         FROM transaction_items ti

         INNER JOIN waste_categories wc
            ON wc.id = ti.category_id

         WHERE ti.transaction_id = ?

         ORDER BY ti.id ASC'
    );


$itemStmt->execute([
    $transaction_id
]);


$items =
    $itemStmt->fetchAll();


/* =========================
   แต้มก่อนทำรายการ
========================= */

$pointsBefore =
    (int)$tx['points_balance']
    -
    (int)$tx['total_points'];

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
    ใบเสร็จ #<?= (int)$tx['id'] ?>
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
    style="max-width:480px;"
>


    <div class="brand">

        <span class="mark">
            ระบบจัดการขยะ
        </span>

    </div>


    <div class="ticket">


        <!-- ตราประทับ -->

        <div class="stamp">

            รับซื้อ
            <br>
            แล้ว

        </div>


        <!-- เลขบิล -->

        <span class="ticket-label">

            เลขที่ #

            <?= str_pad(
                $tx['id'],
                6,
                '0',
                STR_PAD_LEFT
            ) ?>

        </span>


        <div class="section-title">

            ใบเสร็จรับซื้อขยะ

        </div>


        <!-- วันที่ -->

        <p
            class="hint"
            style="margin:-6px 0 16px;"
        >

            <?= date(
                'd/m/Y H:i',
                strtotime($tx['created_at'])
            ) ?>

            น.

        </p>


        <!-- สมาชิก -->

        <div
            style="
                font-size:14px;
                margin-bottom:4px;
            "
        >

            สมาชิก

            <b>
                <?= htmlspecialchars(
                    $tx['full_name']
                ) ?>
            </b>

        </div>


        <div
            style="
                font-size:13px;
                color:var(--color-muted);
                margin-bottom:16px;
            "
        >

            <?= htmlspecialchars(
                $tx['phone']
            ) ?>

        </div>


        <!-- รายการ -->

        <table class="line-items">

            <thead>

                <tr>

                    <th>
                        ประเภท
                    </th>

                    <th class="num">
                        น้ำหนัก
                    </th>

                    <th class="num">
                        ราคา/กก.
                    </th>

                    <th class="num">
                        เงิน
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($items as $item): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars(
                            $item['category_name']
                        ) ?>
                    </td>


                    <td class="num">

                        <?= number_format(
                            (float)$item['weight_kg'],
                            2
                        ) ?>

                    </td>


                    <td class="num">

                        <?= number_format(
                            (float)$item['unit_price'],
                            2
                        ) ?>

                    </td>


                    <td class="num">

                        <?= number_format(
                            (float)$item['amount'],
                            2
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <!-- ยอดรวม -->

        <div class="total-box">

            <div class="lbl">
                ยอดเงินรวมทั้งสิ้น
            </div>

            <div class="amt">

                <?= number_format(
                    (float)$tx['total_amount'],
                    2
                ) ?>

                บาท

            </div>

        </div>


        <!-- แต้ม -->

        <div class="points-box">

            🎉 ได้รับแต้มเพิ่ม

            <b>

                <?= number_format(
                    (int)$tx['total_points']
                ) ?>

                แต้ม

            </b>


            <div class="points-row">

                <span>

                    แต้มก่อนหน้า

                    <?= number_format(
                        $pointsBefore
                    ) ?>

                </span>


                <span>

                    แต้มคงเหลือ

                    <b>

                        <?= number_format(
                            (int)$tx['points_balance']
                        ) ?>

                    </b>

                </span>

            </div>

        </div>


        <!-- ปุ่ม -->

        <div class="actions">

            <button
                class="btn-primary"
                onclick="window.print()"
            >

                🖨️ พิมพ์ใบเสร็จ

            </button>


            <a
                class="btn btn-secondary"
                href="index.php?phone=<?= urlencode($tx['phone']) ?>"
            >

                ➕ ชั่งต่อ

            </a>

        </div>


    </div>


</div>

</body>

</html>