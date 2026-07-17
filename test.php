<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Mobile</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
    body { background: #f0f2f5; padding: 20px; }
    
    /* กล่องหลัก */
    .box-container {
        display: flex;
        flex-direction: row; /* ปกติเรียงเป็นแถวนอนบนคอม */
        gap: 20px;
        background: white;
        padding: 20px;
        border-radius: 12px;
        max-width: 800px;
        margin: 0 auto;
    }
    .left { flex: 1; background: #e0f2fe; padding: 20px; border-radius: 8px; }
    .right { width: 200px; background: #fef08a; padding: 20px; border-radius: 8px; }

    /* โหมดมือถือ */
    @media screen and (max-width: 600px) {
        .box-container {
            flex-direction: column !important; /* บังคับให้ตั้งดิ่งลงมาเท่านั้น */
        }
        .right {
            width: 100% !important;
            order: -1 !important; /* สลับเอาอันขวาขึ้นไปไว้บนสุด */
        }
    }
</style>
</head>
<body>

    <div class="box-container">
        <div class="left">
            <h3>ส่วนฟอร์มกรอกข้อมูล</h3>
            <p>ถ้าย่อจอเหลือขนาดมือถือ ส่วนนี้ต้องลงไปอยู่ข้างล่าง</p>
        </div>
        <div class="right">
            <h3>ส่วนรูปภาพ</h3>
            <p>ถ้าย่อจอเหลือขนาดมือถือ ส่วนนี้ต้องเด้งขึ้นไปอยู่บนสุด</p>
        </div>
    </div>

</body>
</html>