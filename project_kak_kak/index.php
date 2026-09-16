<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$categories = get_active_categories($pdo);

$member = null;
$search_error = '';

if (!empty($_GET['phone'])) {
    $phone = trim($_GET['phone']);

    $member = find_user_by_phone($pdo, $phone);

    if (!$member) {
        $search_error = 'ไม่พบสมาชิกเบอร์นี้ในระบบ กรุณาลงทะเบียนก่อน';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ระบบบริหารจัดการขยะและแลกแต้มรางวัล</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Chonburi&family=Sarabun:wght@400;500;600&family=Kanit:wght@500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="page">

    <div class="brand">
        <span class="mark">ระบบจัดการขยะ</span>
    </div>

    <h1 class="title">ชั่งขยะและรับแต้ม</h1>

    <p class="subtitle">
        ค้นหาสมาชิก แล้วบันทึกรายการขยะที่นำมาขาย
    </p>


    <!-- =========================
         ค้นหาสมาชิก
    ========================== -->

    <div class="ticket">

        <span class="ticket-label">ขั้นตอน 1</span>

        <div class="section-title">
            ค้นหาสมาชิก
        </div>

        <form method="get"
              style="display:flex; gap:10px; align-items:flex-end;">

            <div style="flex:1">

                <label>เบอร์โทรศัพท์</label>

                <input
                    type="text"
                    name="phone"
                    placeholder="เช่น 0812345678"
                    value="<?= htmlspecialchars($_GET['phone'] ?? '') ?>"
                >

            </div>

            <button type="submit" class="btn-secondary">
                🔍 ค้นหา
            </button>

        </form>


        <?php if ($search_error): ?>

            <p class="alert-error" style="margin-top:12px;">
                <?= htmlspecialchars($search_error) ?>
            </p>

            <p class="hint">
                ยังไม่มีสมาชิก?
                <a href="register.php"
                   style="color:var(--color-primary)">
                    ลงทะเบียนสมาชิกใหม่ →
                </a>
            </p>

        <?php elseif ($member): ?>

            <div class="member-info">

                สมาชิก
                <b>
                    <?= htmlspecialchars($member['full_name']) ?>
                </b>

                (<?= htmlspecialchars($member['phone']) ?>)

                <br>

                แต้มสะสมปัจจุบัน
                <b>
                    <?= number_format((int)$member['points_balance']) ?>
                </b>
                แต้ม

            </div>

        <?php endif; ?>

    </div>


    <?php if ($member): ?>

    <!-- =========================
         ชั่งขยะ
    ========================== -->

    <div class="ticket">

        <span class="ticket-label">ขั้นตอน 2</span>

        <div class="section-title">
            รายการขยะ
        </div>

        <form
            method="post"
            action="save_transaction.php"
            id="weighForm"
        >

            <input
                type="hidden"
                name="user_id"
                value="<?= (int)$member['id'] ?>"
            >


            <div id="itemsWrap"></div>


            <button
                type="button"
                class="btn-secondary btn-block"
                onclick="addRow()"
                style="margin-top:4px;"
            >
                + เพิ่มประเภทขยะ
            </button>


            <!-- สรุปยอด -->

            <div class="summary-box">

                <div>

                    <div class="sum-label">
                        ยอดเงินรวม
                    </div>

                    <div class="sum-amount">

                        <span id="sumAmount">
                            0.00
                        </span>

                        บาท

                    </div>

                </div>


                <div class="sum-points">

                    +
                    <span id="sumPoints">
                        0
                    </span>

                    แต้ม

                </div>

            </div>


            <p class="hint">
                ระบบจะคำนวณราคาและแต้มใหม่อีกครั้งที่ฝั่งเซิร์ฟเวอร์
            </p>


            <button
                type="submit"
                class="btn-primary btn-block"
                style="margin-top:14px;"
            >
                💾 บันทึกรายการ
            </button>

        </form>

    </div>

    <?php endif; ?>


    <!-- =========================
         เมนู
    ========================== -->

    <div style="text-align:center; margin-top:20px;">

        <a
            href="register.php"
            class="btn btn-secondary"
        >
            👤 สมัครสมาชิก
        </a>

        <a
            href="rewards.php"
            class="btn btn-secondary"
        >
            🎁 แลกแต้ม
        </a>

        <a
            href="admin.php"
            class="btn btn-secondary"
        >
            ⚙️ ผู้ดูแลระบบ
        </a>

    </div>

</div>


<script>

const categories =
    <?= json_encode(
        $categories,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;

const POINT_RATE =
    <?= (int)POINT_RATE_BAHT_PER_POINT ?>;

let rowCount = 0;


/* =========================
   เพิ่มรายการ
========================= */

function addRow() {

    rowCount++;

    const wrap =
        document.getElementById('itemsWrap');

    const div =
        document.createElement('div');

    div.className = 'item-row';

    div.id = 'row-' + rowCount;


    let options =
        '<option value="">-- เลือกประเภท --</option>';


    categories.forEach(function(category) {

        options += `
            <option
                value="${category.id}"
                data-price="${category.unit_price}"
            >
                ${escapeHtml(category.name)}
                (${parseFloat(category.unit_price).toFixed(2)} บ./กก.)
            </option>
        `;

    });


    div.innerHTML = `

        <div class="col">

            <label>
                ประเภทขยะ
            </label>

            <select
                name="category_id[]"
                onchange="calcAll()"
                required
            >

                ${options}

            </select>

        </div>


        <div class="col narrow">

            <label>
                น้ำหนัก (กก.)
            </label>

            <input
                type="number"
                step="0.01"
                min="0.01"
                name="weight_kg[]"
                oninput="calcAll()"
                required
            >

        </div>


        <div class="col narrow">

            <label>
                ยอดเงิน
            </label>

            <input
                type="text"
                class="lineAmount"
                value="0.00"
                readonly
            >

        </div>


        <button
            type="button"
            class="remove-btn"
            onclick="removeRow(${rowCount})"
        >
            ✕
        </button>

    `;


    wrap.appendChild(div);

    calcAll();
}


/* =========================
   ลบรายการ
========================= */

function removeRow(id) {

    const row =
        document.getElementById('row-' + id);

    if (row) {
        row.remove();
    }

    calcAll();
}


/* =========================
   คำนวณยอดเงิน
========================= */

function calcAll() {

    let total = 0;


    document
        .querySelectorAll('#itemsWrap .item-row')
        .forEach(function(row) {

            const select =
                row.querySelector('select');

            const weightInput =
                row.querySelector(
                    'input[name="weight_kg[]"]'
                );

            const amountBox =
                row.querySelector('.lineAmount');


            let price = 0;

            if (
                select &&
                select.selectedOptions.length > 0
            ) {

                price =
                    parseFloat(
                        select
                            .selectedOptions[0]
                            .dataset.price || 0
                    );

            }


            const weight =
                parseFloat(
                    weightInput.value || 0
                );


            const amount =
                price * weight;


            amountBox.value =
                amount.toFixed(2);


            total += amount;

        });


    document.getElementById('sumAmount')
        .innerText =
        total.toFixed(2);


    document.getElementById('sumPoints')
        .innerText =
        Math.floor(
            total / POINT_RATE
        );

}


/* =========================
   ป้องกัน HTML
========================= */

function escapeHtml(text) {

    const div =
        document.createElement('div');

    div.textContent = text;

    return div.innerHTML;

}


/* เพิ่มแถวแรกอัตโนมัติ */

addRow();



</script>

<!-- =========================================================
     📢 ข่าวสารและโปรโมชั่น
========================================================= -->

<section class="ad-section">

    <div class="ad-container">

        <div class="ad-title">
            <h2>📢 ข่าวสารและโปรโมชั่น</h2>
            <p>ติดตามข่าวสารและสิทธิพิเศษจากเรา</p>
        </div>

        <div class="ad-grid">


            <!-- โฆษณา 1 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=800&q=80"
                        alt="ขยะรีไซเคิล"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        ♻️ โปรโมชั่น
                    </span>

                    <h3>
                        นำขยะมาขาย รับแต้มทันที!
                    </h3>

                    <p>
                        นำขยะรีไซเคิลมาขายกับเรา
                        รับเงินพร้อมสะสมแต้ม
                        เพื่อนำไปแลกของรางวัลมากมาย
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">ดูรายละเอียด →</summary>
                        <div class="ad-detail-content">
                            <h4>♻️ นำขยะมาขาย รับแต้มทันที!</h4>
                            <p>นำขยะรีไซเคิลมาขายกับเรา รับเงินพร้อมสะสมแต้ม เพื่อนำไปแลกของรางวัลมากมาย</p>
                            <p>💡 ยิ่งนำขยะมาขายมาก ยิ่งได้รับแต้มมาก</p>
                        </div>
                    </details>

                </div>

            </article>


            <!-- โฆษณา 2 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=800&q=80"
                        alt="ของรางวัล"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        🎁 ของรางวัล
                    </span>

                    <h3>
                        สะสมแต้ม แลกของรางวัล
                    </h3>

                    <p>
                        ยิ่งนำขยะมาขายมาก
                        ยิ่งได้รับแต้มมาก
                        สามารถนำแต้มไปแลกของรางวัลได้
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">ดูของรางวัล →</summary>
                        <div class="ad-detail-content">
                            <h4>🎁 สะสมแต้ม แลกของรางวัล</h4>
                            <p>นำขยะมาขายและสะสมแต้ม เมื่อมีแต้มเพียงพอสามารถนำมาแลกของรางวัลที่มีอยู่ในระบบได้</p>
                            <a href="rewards.php" class="ad-detail-link">ไปหน้าของรางวัล →</a>
                        </div>
                    </details>

                </div>

            </article>


            <!-- โฆษณา 3 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?auto=format&fit=crop&w=800&q=80"
                        alt="รักษ์โลก"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        🌱 ประชาสัมพันธ์
                    </span>

                    <h3>
                        ช่วยกันรักษ์โลก
                    </h3>

                    <p>
                        เริ่มต้นง่าย ๆ ด้วยการคัดแยกขยะ
                        ก่อนนำมาทิ้งหรือขาย
                        ช่วยลดขยะและดูแลสิ่งแวดล้อม
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">ดูรายละเอียด →</summary>
                        <div class="ad-detail-content">
                            <h4>🌱 ช่วยกันรักษ์โลก</h4>
                            <p>เริ่มต้นง่าย ๆ ด้วยการคัดแยกขยะก่อนนำมาทิ้งหรือขาย ช่วยลดปริมาณขยะและดูแลสิ่งแวดล้อมให้ดีขึ้น</p>
                        </div>
                    </details>

                </div>

            </article>


            <!-- โฆษณา 4 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?auto=format&fit=crop&w=800&q=80"
                        alt="คัดแยกขยะ"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        🌍 รักษ์โลก
                    </span>

                    <h3>
                        คัดแยกขยะก่อนทิ้ง
                    </h3>

                    <p>
                        การคัดแยกขยะช่วยให้สามารถนำวัสดุ
                        กลับมาใช้ประโยชน์ได้มากขึ้น
                        และช่วยลดปัญหาสิ่งแวดล้อม
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">ดูรายละเอียด →</summary>
                        <div class="ad-detail-content">
                            <h4>🌍 คัดแยกขยะก่อนทิ้ง</h4>
                            <p>การคัดแยกขยะช่วยให้วัสดุต่าง ๆ สามารถนำกลับมาใช้ประโยชน์ได้มากขึ้น และช่วยลดปัญหาสิ่งแวดล้อม</p>
                        </div>
                    </details>

                </div>

            </article>


            <!-- โฆษณา 5 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80"
                        alt="สิ่งแวดล้อม"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        💚 กิจกรรม
                    </span>

                    <h3>
                        ร่วมสร้างสิ่งแวดล้อมที่ดี
                    </h3>

                    <p>
                        ทุกคนสามารถช่วยกันดูแลสิ่งแวดล้อมได้
                        เพียงเริ่มจากการลดและคัดแยกขยะ
                        ในชีวิตประจำวัน
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">ดูรายละเอียด →</summary>
                        <div class="ad-detail-content">
                            <h4>💚 ร่วมสร้างสิ่งแวดล้อมที่ดี</h4>
                            <p>ทุกคนสามารถช่วยกันดูแลสิ่งแวดล้อมได้ เพียงเริ่มจากการลดขยะ ใช้ซ้ำ และคัดแยกขยะในชีวิตประจำวัน</p>
                        </div>
                    </details>

                </div>

            </article>


            <!-- โฆษณา 6 -->
            <article class="ad-card">

                <div class="ad-icon">
                    <img
                        src="https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=800&q=80"
                        alt="กิจกรรมชุมชน"
                    >
                </div>

                <div class="ad-content">

                    <span class="ad-badge">
                        📢 ข่าวสาร
                    </span>

                    <h3>
                        ร่วมเป็นส่วนหนึ่งของชุมชน
                    </h3>

                    <p>
                        มาร่วมกันดูแลชุมชนของเรา
                        ให้สะอาด น่าอยู่
                        และเป็นมิตรกับสิ่งแวดล้อม
                    </p>

                    <details class="ad-details">
                        <summary class="ad-button">สมัครสมาชิก →</summary>
                        <div class="ad-detail-content">
                            <h4>📢 ร่วมเป็นส่วนหนึ่งของชุมชน</h4>
                            <p>สมัครสมาชิกเพื่อเริ่มนำขยะมาขาย สะสมแต้ม และติดตามสิทธิประโยชน์ต่าง ๆ ของระบบ</p>
                            <a href="register.php" class="ad-detail-link">ไปหน้าสมัครสมาชิก →</a>
                        </div>
                    </details>

                </div>

            </article>


        </div>

    </div>

</section>




<style>
.ad-details {
    width: 100%;
}
.ad-details summary {
    list-style: none;
}
.ad-details summary::-webkit-details-marker {
    display: none;
}
.ad-details .ad-button {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-sizing: border-box;
}
.ad-detail-content {
    margin-top: 10px;
    padding: 16px 18px;
    border-radius: 14px;
    background: #f3faf5;
    border: 1px solid #d7eadc;
    text-align: left;
}
.ad-detail-content h4 {
    margin: 0 0 8px;
    color: #183126;
    line-height: 1.5;
}
.ad-detail-content p {
    margin: 0 0 8px;
    line-height: 1.7;
}
.ad-detail-content p:last-child {
    margin-bottom: 0;
}
.ad-detail-link {
    display: inline-block;
    margin-top: 6px;
    color: #149447;
    font-weight: 600;
    text-decoration: none;
}
.ad-detail-link:hover {
    text-decoration: underline;
}
</style>

</body>
</html>