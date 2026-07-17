<?php // register.php ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register | FreshFast</title>

<style>
body {
  margin: 0;
  font-family: sans-serif;
  background: #f3f3f3;
}

/* ===== LAYOUT ===== */
.page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

.shell {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 920px;
  height: 560px;
  border-radius: 20px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

/* ===== HERO ===== */
.hero {
  position: relative;
}

.hero img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.25);
}

.headline {
  position: absolute;
  bottom: 30px;
  left: 25px;
  right: 25px;
  color: #fff;
  font-size: 20px;
  font-weight: 600;
}

/* ===== RIGHT ===== */
.card {
  display: flex;
  flex-direction: column;
  padding: 20px 35px 30px;
  background: #eef0ee;
  overflow-y: auto;
}

.brand {
  text-align: center;
}

.logo {
  width: 160px;
  margin-bottom: 10px;
}

.h1 {
  font-size: 22px;
  margin: 0;
}

/* ===== FORM ===== */
.form {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.label {
  font-size: 14px;
  font-weight: 600;
}

.input {
  padding: 12px 16px;
  border-radius: 25px;
  border: 1px solid #ddd;
  outline: none;
}

.input:focus {
  border-color: #f5c542;
}

/* ===== CHECKBOX ===== */
.checkrow {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 14px;
}

.checkrow a {
  color: #2e7d32;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.checkrow a:hover {
  opacity: .8;
}

/* ===== BUTTON ===== */
.btn {
  padding: 12px;
  border-radius: 25px;
  border: none;
  background: #f5c542;
  cursor: pointer;
  font-weight: 600;
}

.btn.green {
  background: #2e7d32;
  color: #fff;
}

/* ===== MODAL ===== */
.modal {
  position: fixed;
  inset: 0;
  display: none;
  justify-content: center;
  align-items: center;
  background: rgba(0,0,0,0.5);
}

.modal.show {
  display: flex;
}

.modal-box {
  background: #fff;
  padding: 20px;
  width: 90%;
  max-width: 600px;
  border-radius: 12px;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-body {
  font-size: 14px;
  line-height: 1.8;
  text-align: justify;
}

.modal-body p {
  margin-bottom: 16px;
  text-indent: 30px;
}

.modal-close {
  float: right;
  border: none;
  background: none;
  font-size: 20px;
  cursor: pointer;
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
  .shell {
    grid-template-columns: 1fr;
    width: 90%;
    height: auto;
  }
  .hero { display: none; }
}
</style>
</head>

<body>

<div class="page">
<div class="shell">

<!-- LEFT -->
<div class="hero">
  <img src="assets/images/hero-market.png">
  <div class="overlay"></div>
  <div class="headline">
    แพลตฟอร์มที่ส่งเสริมการอุดหนุนคนแม่สอด<br>
    และการพัฒนาชุมชนอย่างยั่งยืน
  </div>
</div>

<!-- RIGHT -->
<div class="card">
  <div class="brand">
    <img src="assets/images/logo_ok.png" class="logo">
    <h1 class="h1">ยินดีต้อนรับ!<br>ลงทะเบียนเพื่อเริ่มช้อปได้เลย</h1>
  </div>

  <form class="form" action="do_register.php" method="post" autocomplete="off">

    <div class="label">ชื่อ</div>
    <input class="input" name="name" type="text" placeholder="เช่น ประภา ใจดี" required>

    <div class="label">อีเมล</div>
    <input class="input" name="email" type="email" placeholder="เช่น example@email.com" required>

    <div class="label">รหัสผ่าน</div>
    <input class="input" name="password" type="password" placeholder="อย่างน้อย 8 ตัวอักษร" required>

    <input type="hidden" name="consent_version" value="1.0">

    <div class="checkrow">
      <input type="checkbox" id="accept" name="consent" value="1" required>
      <label for="accept">
        ฉันยอมรับ
        <a href="#" id="openTerms">ข้อกำหนดการใช้งาน</a>
        และ
        <a href="#" id="openPrivacy">นโยบายความเป็นส่วนตัว</a>
      </label>
    </div>

    <div style="text-align:center;margin-top:10px;">
      <button class="btn">ลงทะเบียน</button>
    </div>
  </form>
</div>

</div>
</div>

<!-- TERMS MODAL -->
<div id="termsModal" class="modal">
  <div class="modal-box">
    <button class="modal-close" id="closeTerms">×</button>
    <h2>ข้อกำหนดและเงื่อนไขการใช้งาน</h2>

    <p>1. การใช้งานแพลตฟอร์ม FreshFast ถือว่าผู้ใช้งานยอมรับข้อกำหนดและเงื่อนไขทั้งหมดที่ระบุไว้ในเอกสารนี้ หากผู้ใช้งานไม่ยอมรับข้อกำหนดดังกล่าว กรุณาหยุดใช้งานแพลตฟอร์มทันที</p>

    <p>2. ผู้ใช้งานต้องให้ข้อมูลที่ถูกต้อง ครบถ้วน และเป็นปัจจุบันในการสมัครสมาชิกและใช้งานบัญชีของตนเอง โดยผู้ใช้งานมีหน้าที่รับผิดชอบในการรักษาความลับของบัญชีและรหัสผ่าน</p>

    <p>3. ห้ามมิให้ผู้ใช้งานนำแพลตฟอร์มไปใช้ในทางที่ผิดกฎหมาย ผิดศีลธรรม หรือก่อให้เกิดความเสียหายต่อระบบ ผู้ขาย หรือผู้ใช้งานรายอื่น</p>

    <p>4. FreshFast ขอสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีผู้ใช้งานที่ฝ่าฝืนข้อกำหนดโดยไม่ต้องแจ้งให้ทราบล่วงหน้า</p>

    <p>5. สินค้า ราคา โปรโมชั่น และข้อมูลต่าง ๆ บนแพลตฟอร์มอาจมีการเปลี่ยนแปลงได้โดยไม่ต้องแจ้งล่วงหน้า</p>

    <p>6. FreshFast ไม่รับประกันว่าระบบจะสามารถให้บริการได้อย่างต่อเนื่องโดยปราศจากข้อผิดพลาดหรือการหยุดชะงักตลอดเวลา</p>

    <p>7. FreshFast ขอสงวนสิทธิ์ในการแก้ไข เปลี่ยนแปลง หรือปรับปรุงข้อกำหนดการใช้งานได้ทุกเมื่อ โดยการเปลี่ยนแปลงจะมีผลทันทีเมื่อเผยแพร่บนแพลตฟอร์ม</p>

    <div class="modal-body">
      <p>Policy Version: 1.0</p>
    </div>
  </div>
</div>

<!-- PRIVACY MODAL -->
<div id="privacyModal" class="modal">
  <div class="modal-box">
    <button class="modal-close" id="closePrivacy">×</button>
    <h2>นโยบายความเป็นส่วนตัว</h2>

    <p>1. FreshFast ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้งาน และมุ่งมั่นในการปกป้องข้อมูลส่วนบุคคลของท่านตามกฎหมายที่เกี่ยวข้อง</p>

    <p>2. ข้อมูลที่เราอาจเก็บรวบรวม ได้แก่ ชื่อ อีเมล เบอร์โทรศัพท์ ที่อยู่จัดส่ง ประวัติคำสั่งซื้อ และข้อมูลการใช้งานแพลตฟอร์ม</p>

    <p>3. ข้อมูลของท่านจะถูกใช้เพื่อวัตถุประสงค์ในการให้บริการ เช่น การสร้างบัญชี การประมวลผลคำสั่งซื้อ การจัดส่งสินค้า การติดต่อเกี่ยวกับบริการ และการปรับปรุงประสบการณ์การใช้งาน</p>

    <p>4. FreshFast จะไม่เปิดเผยข้อมูลส่วนบุคคลแก่บุคคลภายนอก เว้นแต่เป็นการจำเป็นเพื่อให้บริการ เช่น ผู้ให้บริการขนส่ง ผู้ประมวลผลการชำระเงิน หรือเป็นไปตามข้อกำหนดของกฎหมาย</p>

    <p>5. ข้อมูลของท่านจะถูกจัดเก็บด้วยมาตรการรักษาความปลอดภัยที่เหมาะสมเพื่อป้องกันการเข้าถึง การเปิดเผย หรือการใช้งานโดยไม่ได้รับอนุญาต</p>

    <p>6. ผู้ใช้งานมีสิทธิ์ในการเข้าถึง แก้ไข หรือลบข้อมูลส่วนบุคคลของตน รวมถึงถอนความยินยอมในการประมวลผลข้อมูลได้ โดยติดต่อผ่านช่องทางที่กำหนด</p>

    <p>7. FreshFast อาจมีการปรับปรุงนโยบายความเป็นส่วนตัวเป็นครั้งคราว และจะแจ้งให้ทราบผ่านแพลตฟอร์มเมื่อมีการเปลี่ยนแปลงที่สำคัญ</p>

    <div class="modal-body">
      
    </div>
  </div>
</div>

<!-- DUP MODAL -->
<div id="dupModal" class="modal">
  <div class="modal-box" style="text-align:center;">
    <button class="modal-close" id="closeDup">×</button>
    <h2>มีบัญชีนี้อยู่แล้ว</h2>

    <a class="btn green" href="login.php">ไปหน้าเข้าสู่ระบบ</a>
  </div>
</div>

<script>
// TERMS
const termsModal = document.getElementById('termsModal');
const privacyModal = document.getElementById('privacyModal');

document.getElementById('openTerms').onclick = e => {
  e.preventDefault();
  termsModal.classList.add('show');
};

document.getElementById('openPrivacy').onclick = e => {
  e.preventDefault();
  privacyModal.classList.add('show');
};

document.getElementById('closeTerms').onclick = () => termsModal.classList.remove('show');
document.getElementById('closePrivacy').onclick = () => privacyModal.classList.remove('show');

// CLICK OUTSIDE CLOSE
document.querySelectorAll(".modal").forEach(m=>{
  m.addEventListener("click", e=>{
    if(e.target === m) m.classList.remove("show");
  });
});

// DUP
(function(){
  const url = new URL(window.location.href);
  if(url.searchParams.get('dup') === '1'){
    document.getElementById('dupModal').classList.add('show');
  }

  document.getElementById('closeDup').onclick = ()=>{
    document.getElementById('dupModal').classList.remove('show');
    url.searchParams.delete('dup');
    window.history.replaceState({}, '', url.toString());
  };
})();
</script>

</body>
</html>