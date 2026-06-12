<?php
/**
 * layout/footer.php
 * Global HTML footer — penutup semua halaman terproteksi
 */
?>
  </div><!-- /.erp-content -->
</main><!-- /.erp-main -->
</div><!-- /.erp-wrapper -->

<!-- Bootstrap 5 Bundle (JS + Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>

<!-- Custom App JS -->
<script src="<?= asset('js/app.js') ?>"></script>

<?php if (isset($extraJs)): ?>
  <!-- Extra JS dari halaman tertentu -->
  <?= $extraJs ?>
<?php endif; ?>

</body>
</html>
