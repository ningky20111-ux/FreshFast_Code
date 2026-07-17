<script>
const avatarBtn = document.getElementById('avatarBtn');
const dropdown = document.getElementById('profileDropdown');

if (avatarBtn && dropdown) {
  avatarBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('show');
  });

  document.addEventListener('click', () => {
    dropdown.classList.remove('show');
  });
}

const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");
const overlay = document.getElementById("mobileOverlay");

if (menuBtn) {
  menuBtn.addEventListener("click", () => {
    mobileMenu.classList.add("show");
    overlay.classList.add("show");
  });
}

if (overlay) {
  overlay.addEventListener("click", () => {
    mobileMenu.classList.remove("show");
    overlay.classList.remove("show");
  });
}
</script>