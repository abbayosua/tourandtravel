<?php
/**
 * includes/components/pax-modal.php — modal data peserta + paspor (multi-peserta).
 *
 * Variabel yang harus diset sebelum require:
 *   $paxForm           string  id form yang akan menerima input (wajib)
 *   $paxCountSelector  string  selector input jumlah peserta (wajib, mis. 'input[name="participants"]')
 *   $paxShowSelf       bool    tampilkan checkbox "saya ikut tour" (default true)
 *   $paxSelfChecked    bool    nilai awal checkbox (default true)
 */
$paxForm = $paxForm ?? '';
$paxCountSelector = $paxCountSelector ?? 'input[name="participants"]';
$paxShowSelf = $paxShowSelf ?? true;
$paxSelfChecked = $paxSelfChecked ?? true;
?>
<div class="modal fade" id="paxDataModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-people me-2"></i><?= t('Data Peserta & Paspor') ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= t('Tutup') ?>"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i><?= t('Isi nama lengkap (sesuai paspor) dan unggah foto paspor untuk setiap peserta.') ?> <span class="text-muted"><?= t('Format JPG/PNG/WebP, max 5MB') ?></span></div>
        <div id="paxRows" data-testid="pax-rows"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" data-testid="pax-done"><?= t('Selesai') ?></button>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
    var FORM_ID  = <?= json_encode($paxForm) ?>;
    var COUNT_SEL = <?= json_encode($paxCountSelector) ?>;
    var SHOW_SELF = <?= $paxShowSelf ? 'true' : 'false' ?>;
    var paxInput = document.querySelector(COUNT_SEL);
    var selfChk  = document.getElementById('selfIncludedTour');
    var nameEl   = document.getElementById('bookingName') || document.getElementById('resellerName');
    var rowsEl   = document.getElementById('paxRows');
    var countEl  = document.getElementById('paxDataCount');
    var modalEl  = document.getElementById('paxDataModal');
    var btn      = document.getElementById('paxDataBtn');
    if (!paxInput || !rowsEl || !modalEl) return;
    var CSRF = '<?= e(csrfToken()) ?>';
    var modal = null;
    var uploaded = {};

    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : s); return d.innerHTML; }

    function render() {
        var n = Math.max(1, parseInt(paxInput.value, 10) || 1);
        var self = SHOW_SELF && !!(selfChk && selfChk.checked);
        if (countEl) countEl.textContent = n;
        rowsEl.innerHTML = '';
        for (var i = 1; i <= n; i++) {
            var isSelf = (i === 1 && self);
            var val = isSelf ? (nameEl ? nameEl.value : '') : '';
            var done = !!uploaded[i];
            var row = document.createElement('div');
            row.className = 'border rounded p-2 mb-2';
            row.innerHTML =
                '<div class="mb-1"><span class="badge bg-light text-dark"><?= e(t('Peserta')) ?> ' + i + (isSelf ? ' · <?= e(t('Pemesan')) ?>' : '') + '</span></div>' +
                '<div class="mb-2"><input type="text" form="' + FORM_ID + '" name="pax_name_' + i + '" class="form-control form-control-sm" placeholder="<?= e(t('Nama lengkap sesuai paspor')) ?>"' + (isSelf ? ' readonly' : '') + ' value="' + esc(val) + '"></div>' +
                '<div><input type="file" class="form-control form-control-sm pax-file" data-idx="' + i + '" accept="image/jpeg,image/png,image/webp">' +
                '<input type="hidden" form="' + FORM_ID + '" name="passport_file_' + i + '" id="passportFile' + i + '" value="' + esc(uploaded[i] || '') + '">' +
                '<div class="form-text pax-status" id="paxStatus' + i + '">' + (done ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> <?= e(t('Terunggah')) ?></span>' : '') + '</div></div>';
            rowsEl.appendChild(row);
        }
    }

    function compressPax(file) {
        return new Promise(function (resolve) {
            if (!file || !/^image\//.test(file.type) || typeof document.createElement('canvas').toBlob !== 'function') { resolve(file); return; }
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                try {
                    var MAX = 1600, w = img.naturalWidth, h = img.naturalHeight;
                    if (w > MAX) { var r = MAX / w; h = Math.round(h * r); w = MAX; }
                    var c = document.createElement('canvas');
                    c.width = w; c.height = h;
                    c.getContext('2d').drawImage(img, 0, 0, w, h);
                    c.toBlob(function (blob) {
                        URL.revokeObjectURL(url);
                        if (blob && blob.size > 0 && blob.size < file.size) {
                            resolve(new File([blob], 'passport.jpg', { type: 'image/jpeg' }));
                        } else {
                            resolve(file);
                        }
                    }, 'image/jpeg', 0.82);
                } catch (e) { URL.revokeObjectURL(url); resolve(file); }
            };
            img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    function uploadPax(inp) {
        var i = inp.getAttribute('data-idx');
        var hid = document.getElementById('passportFile' + i);
        var st = document.getElementById('paxStatus' + i);
        if (hid) hid.value = '';
        if (!inp.files || !inp.files[0]) { if (st) st.innerHTML = ''; return; }
        inp.disabled = true;
        if (st) st.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span><?= e(t('Mengunggah...')) ?></span>';
        compressPax(inp.files[0])
            .then(function (file) {
                var fd = new FormData();
                fd.append('csrf_token', CSRF);
                fd.append('passport', file);
                return fetch('pax-upload-ajax.php', { method: 'POST', body: fd });
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                inp.disabled = false;
                if (d.success) {
                    uploaded[i] = d.filename;
                    if (hid) hid.value = d.filename;
                    if (st) st.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> <?= e(t('Terunggah')) ?></span>';
                } else {
                    delete uploaded[i];
                    inp.value = '';
                    if (st) st.innerHTML = '<span class="text-danger">' + esc(d.message || '<?= e(t('Gagal mengunggah')) ?>') + '</span>';
                }
            })
            .catch(function () {
                inp.disabled = false;
                delete uploaded[i];
                inp.value = '';
                if (st) st.innerHTML = '<span class="text-danger"><?= e(t('Gagal mengunggah')) ?></span>';
            });
    }

    rowsEl.addEventListener('change', function (ev) {
        var t = ev.target;
        if (t && t.classList && t.classList.contains('pax-file')) uploadPax(t);
    });

    function openModal() {
        render();
        if (!modal && typeof bootstrap !== 'undefined') modal = new bootstrap.Modal(modalEl);
        if (modal) modal.show();
    }

    if (btn) btn.addEventListener('click', openModal);
    paxInput.addEventListener('change', openModal);
    if (selfChk) selfChk.addEventListener('change', function () { delete uploaded[1]; render(); });
    if (nameEl) nameEl.addEventListener('input', function () {
        if (SHOW_SELF && selfChk && selfChk.checked) {
            var f = rowsEl.querySelector('input[name="pax_name_1"]');
            if (f) f.value = nameEl.value;
        }
    });
    render();
})();
</script>
