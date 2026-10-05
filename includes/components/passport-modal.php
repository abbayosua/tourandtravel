<?php
/**
 * includes/components/passport-modal.php — penampil foto paspor (overlay).
 *
 * Tombol pemicu cukup menyertakan atribut: data-passport="<path gambar>".
 * Dipakai overlay kustom (bukan modal Bootstrap) agar aman dibuka dari dalam
 * modal Bootstrap lain (mis. modal Peserta admin) tanpa menutup modal di bawahnya.
 */
?>
<div id="passportViewer" class="passport-viewer" hidden>
    <div class="passport-viewer__backdrop" data-passport-close></div>
    <div class="passport-viewer__panel" role="dialog" aria-modal="true" aria-label="<?= t('Foto Paspor') ?>">
        <button type="button" class="passport-viewer__close" data-passport-close aria-label="<?= t('Tutup') ?>">&times;</button>
        <img id="passportViewerImg" src="" alt="<?= t('Foto Paspor') ?>" data-testid="passport-modal-img">
    </div>
</div>
<style>
.passport-viewer { position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px; }
.passport-viewer[hidden] { display: none; }
.passport-viewer__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.75); }
.passport-viewer__panel { position: relative; max-width: 900px; max-height: 92vh; background: #fff; border-radius: .5rem; padding: .5rem; box-shadow: 0 10px 40px rgba(0,0,0,.4); }
.passport-viewer__panel img { display: block; max-width: 100%; max-height: 88vh; border-radius: .375rem; }
.passport-viewer__close { position: absolute; top: -14px; right: -14px; width: 34px; height: 34px; border: 0; border-radius: 50%; background: #fff; color: #333; font-size: 22px; line-height: 1; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
</style>
<script>
(function () {
    var viewer = document.getElementById('passportViewer');
    if (!viewer) return;
    var img = document.getElementById('passportViewerImg');
    var prevOverflow = '';

    function open(src) {
        if (!src) return;
        prevOverflow = document.body.style.overflow;
        img.src = src;
        viewer.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function close() {
        viewer.hidden = true;
        img.src = '';
        document.body.style.overflow = prevOverflow;
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-passport]');
        if (trigger) { e.preventDefault(); open(trigger.getAttribute('data-passport')); return; }
        if (e.target.closest('[data-passport-close]')) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !viewer.hidden) close();
    });
})();
</script>
