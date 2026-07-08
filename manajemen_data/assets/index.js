// Script tambahan untuk index.php
document.addEventListener("DOMContentLoaded", () => {
  console.log("Index.js loaded");

  // Expand/collapse submenu
  document.querySelectorAll('.toggle-submenu').forEach(toggle => {
    toggle.addEventListener('click', e => {
      e.preventDefault();
      const parent = toggle.parentElement;
      parent.classList.toggle('active');
    });
  });
});
