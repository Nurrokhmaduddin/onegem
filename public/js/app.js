/**
 * public/js/app.js
 * Global JavaScript — ERP Toko Berlian
 * Sprint 2: BASE_URL global, currency formatter, barcode helper
 */

$(function () {

  /* ── BASE_URL global ───────────────────────────────────────────────────── */
  window.BASE_URL = $('meta[name="base-url"]').attr('content') || '';

  /* ── CSRF ──────────────────────────────────────────────────────────────── */
  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

  /* ── Sidebar toggle ────────────────────────────────────────────────────── */
  $('#sidebarToggle').on('click', function () {
    if (window.innerWidth <= 768) {
      $('body').toggleClass('sidebar-open');
    } else {
      $('body').toggleClass('sidebar-collapsed');
      localStorage.setItem('sidebarCollapsed', $('body').hasClass('sidebar-collapsed'));
    }
  });
  if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 768) {
    $('body').addClass('sidebar-collapsed');
  }
  $(document).on('click', function (e) {
    if (window.innerWidth <= 768
      && !$(e.target).closest('#erpSidebar, #sidebarToggle').length) {
      $('body').removeClass('sidebar-open');
    }
  });

  /* ── Auto-dismiss flash success ────────────────────────────────────────── */
  setTimeout(function () {
    $('.alert.alert-success').fadeOut(500, function () { $(this).remove(); });
  }, 5000);

  /* ── Konfirmasi aksi berbahaya ─────────────────────────────────────────── */
  $(document).on('click', '[data-confirm]', function (e) {
    if (!confirm($(this).data('confirm') || 'Apakah Anda yakin?')) {
      e.preventDefault(); return false;
    }
  });

  /* ── Toast helper ──────────────────────────────────────────────────────── */
  window.erpToast = function (type, message) {
    const icons = {
      success:'bi-check-circle-fill', danger:'bi-x-circle-fill',
      warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill',
    };
    const id = 'toast_' + Date.now();
    const html = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2"
         role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${icons[type]||'bi-info-circle-fill'}"></i>${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div></div>`;
    if (!$('#erpToastContainer').length) {
      $('body').append('<div id="erpToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>');
    }
    $('#erpToastContainer').append(html);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 4000 }).show();
    el.addEventListener('hidden.bs.toast', function () { $(this).remove(); });
  };

  /* ── AJAX helper ───────────────────────────────────────────────────────── */
  window.erpAjax = function (options) {
    const cfg = $.extend({
      method:'POST', contentType:'application/x-www-form-urlencoded',
      beforeSend:function(){}, onSuccess:function(){},
      onError:function(msg){ erpToast('danger',msg); },
    }, options);
    return $.ajax({ url:cfg.url, method:cfg.method, data:cfg.data, contentType:cfg.contentType, beforeSend:cfg.beforeSend })
      .done(function(res){
        if (res.success) cfg.onSuccess(res);
        else cfg.onError(res.message||'Terjadi kesalahan.');
      })
      .fail(function(xhr){
        cfg.onError(xhr.responseJSON?.message||'Permintaan gagal. Coba lagi.');
      });
  };

  /* ── Currency helpers ──────────────────────────────────────────────────── */
  window.formatIDR = function(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  };
  window.formatUSD = function(n) {
    return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
  };

  /* ── Barcode input: Enter = submit ─────────────────────────────────────── */
  $(document).on('keydown', '.barcode-input', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).closest('form').submit(); }
  });

  /* ── Password strength ─────────────────────────────────────────────────── */
  $(document).on('input', '.input-password', function() {
    const pw=this.value, bar=$(this).closest('.mb-3').find('.pw-strength');
    if (!bar.length) return;
    let s=0;
    if (pw.length>=8) s++; if (/[A-Z]/.test(pw)) s++; if (/[a-z]/.test(pw)) s++;
    if (/[0-9]/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++;
    bar.css({background:['','#EF4444','#F97316','#EAB308','#22C55E','#16A34A'][s],
             width:['0%','20%','40%','60%','80%','100%'][s]});
  });

  /* ── Show/hide password ────────────────────────────────────────────────── */
  $(document).on('click', '.show-pw-btn', function() {
    const inp=$('#'+($(this).data('target')||'inputPw')), icon=$(this).find('i');
    if (inp.attr('type')==='password') { inp.attr('type','text'); icon.removeClass('bi-eye').addClass('bi-eye-slash'); }
    else { inp.attr('type','password'); icon.removeClass('bi-eye-slash').addClass('bi-eye'); }
  });

  /* ── Prevent double submit ─────────────────────────────────────────────── */
  $(document).on('submit', 'form.no-double-submit', function() {
    $(this).find('[type=submit]').prop('disabled',true)
      .html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');
  });

  /* ── Auto uppercase ────────────────────────────────────────────────────── */
  $(document).on('input', '.input-uppercase', function() {
    const pos=this.selectionStart; this.value=this.value.toUpperCase();
    this.setSelectionRange(pos,pos);
  });

  /* ── Select all checkbox ───────────────────────────────────────────────── */
  $(document).on('change', '.check-all', function() {
    $(this).closest('table').find('tbody .check-item').prop('checked',$(this).is(':checked'));
  });

});
