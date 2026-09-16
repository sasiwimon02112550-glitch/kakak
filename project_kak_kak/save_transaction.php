<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';


/* =========================
   ตรวจสอบ Method
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    die('ไม่อนุญาตให้เข้าหน้านี้โดยตรง');

}


/* =========================
   รับข้อมูล
========================= */

$user_id =
    (int)($_POST['user_id'] ?? 0);

$category_ids =
    $_POST['category_id'] ?? [];

$weights =
    $_POST['weight_kg'] ?? [];


/* =========================
   ตรวจสอบข้อมูลเบื้องต้น
========================= */

if ($user_id <= 0) {

    die('ไม่พบข้อมูลสมาชิก');

}


if (
    !is_array($category_ids) ||
    !is_array($weights) ||
    empty($category_ids)
) {

    die(
        'กรุณาเพิ่มรายการขยะอย่างน้อย 1 รายการ'
    );

}


if (
    count($category_ids) !==
    count($weights)
) {

    die(
        'ข้อมูลรายการขยะไม่ถูกต้อง'
    );

}


/* =========================
   ตรวจสอบสมาชิก
========================= */

$userStmt =
    $pdo->prepare(
        'SELECT id, full_name, phone, points_balance
         FROM users
         WHERE id = ?'
    );

$userStmt->execute([
    $user_id
]);

$user =
    $userStmt->fetch();


if (!$user) {

    die(
        'ไม่พบสมาชิกนี้ในระบบ'
    );

}


/* =========================
   โหลดราคาขยะจาก Database
========================= */

$categoryStmt =
    $pdo->query(
        'SELECT id, name, unit_price
         FROM waste_categories
         WHERE is_active = 1'
    );


$categoryMap = [];


foreach (
    $categoryStmt->fetchAll()
    as $category
) {

    $categoryMap[
        (int)$category['id']
    ] = $category;

}


/* =========================
   คำนวณรายการ
========================= */

$validItems = [];

$totalAmount = 0;


foreach (
    $category_ids
    as $index => $category_id
) {

    $category_id =
        (int)$category_id;


    $weight =
        (float)($weights[$index] ?? 0);


    /* น้ำหนักต้องมากกว่า 0 */

    if ($weight <= 0) {
        continue;
    }


    /* ตรวจสอบประเภทขยะ */

    if (
        !isset(
            $categoryMap[$category_id]
        )
    ) {

        continue;

    }


    $category =
        $categoryMap[$category_id];


    $unit_price =
        (float)$category['unit_price'];


    /* คำนวณเงิน */

    $amount =
        calc_item_amount(
            $weight,
            $unit_price
        );


    $validItems[] = [

        'category_id' =>
            $category_id,

        'weight_kg' =>
            $weight,

        'unit_price' =>
            $unit_price,

        'amount' =>
            $amount

    ];


    $totalAmount += $amount;

}


/* =========================
   ไม่มีรายการที่ถูกต้อง
========================= */

if (empty($validItems)) {

    die(
        'ไม่มีรายการขยะที่ถูกต้อง'
    );

}


/* =========================
   คำนวณยอดรวม
========================= */

$totalAmount =
    round(
        $totalAmount,
        2
    );


$totalPoints =
    calc_points_from_amount(
        $totalAmount
    );


/* =========================
   เริ่ม Transaction
========================= */

try {

    $pdo->beginTransaction();


    /* =========================
       1. สร้างหัวบิล
    ========================== */

    $insertTransaction =
        $pdo->prepare(
            'INSERT INTO transactions
            (
                user_id,
                total_amount,
                total_points
            )
            VALUES (?, ?, ?)'
        );


    $insertTransaction->execute([

        $user_id,

        $totalAmount,

        $totalPoints

    ]);


    $transactionId =
        $pdo->lastInsertId();


    /* =========================
       2. บันทึกรายการขยะ
    ========================== */

    $insertItem =
        $pdo->prepare(
            'INSERT INTO transaction_items
            (
                transaction_id,
                category_id,
                weight_kg,
                unit_price,
                amount
            )
            VALUES (?, ?, ?, ?, ?)'
        );


    foreach (
        $validItems
        as $item
    ) {

        $insertItem->execute([

            $transactionId,

            $item['category_id'],

            $item['weight_kg'],

            $item['unit_price'],

            $item['amount']

        ]);

    }


    /* =========================
       3. เพิ่มแต้มสมาชิก
    ========================== */

    $updateUser =
        $pdo->prepare(
            'UPDATE users
             SET points_balance =
                 points_balance + ?
             WHERE id = ?'
        );


    $updateUser->execute([

        $totalPoints,

        $user_id

    ]);


    /* =========================
       4. บันทึกประวัติแต้ม
    ========================== */

    $insertHistory =
        $pdo->prepare(
            'INSERT INTO point_history
            (
                user_id,
                points,
                reason,
                ref_transaction_id
            )
            VALUES (?, ?, ?, ?)'
        );


    $insertHistory->execute([

        $user_id,

        $totalPoints,

        'ได้รับแต้มจากการขายขยะ',

        $transactionId

    ]);


    /* =========================
       ยืนยัน Transaction
    ========================== */

    $pdo->commit();


} catch (Throwable $e) {


    /* ยกเลิกถ้ามี Error */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die(
        'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' .
        htmlspecialchars($e->getMessage())
    );

}


/* =========================
   ไปหน้าใบเสร็จ
========================= */

header(
    'Location: receipt.php?id=' .
    urlencode($transactionId)
);

exit;