<footer class="footer">
  <div class="footer-left">
    <span class="footer-user">
      User: <?= htmlspecialchars($_SESSION['nama_pegawai']) ?> 
      (<?= htmlspecialchars($_SESSION['id_user']) ?>)
    </span>
    <span class="footer-ip">
      <?= htmlspecialchars($_SERVER['REMOTE_ADDR']) ?>
    </span>
  </div>
  <div class="footer-right" id="clock">
    <!-- isi awal dari PHP -->
    <?= strftime("%A, %d %B %Y %H:%M:%S") ?>
  </div>
</footer>

<script>
function updateClock() {
  const now = new Date();
  const options = { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit', 
    second: '2-digit' 
  };
  document.getElementById('clock').textContent = 
    now.toLocaleDateString('id-ID', options);
}
setInterval(updateClock, 1000); // update tiap detik
updateClock(); // panggil sekali di awal
</script>
