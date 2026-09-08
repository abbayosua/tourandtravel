<?php
/**
 * Social Proof Toast — widget ticker "X baru saja memesan".
 * Fetch social-proof-ajax.php, tampil toast bergantian kanan-bawah.
 * Sad path: response kosong/gagal → widget tidak muncul, tanpa error.
 */
?>
<div id="socialProofToast" class="position-fixed bottom-0 end-0 p-3 d-none" style="z-index: 1080;" data-testid="social-proof-toast">
    <div class="card border-0 shadow-sm" style="max-width: 300px;">
        <div class="card-body p-2 d-flex align-items-center gap-2">
            <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                <i class="bi bi-bag-check"></i>
            </div>
            <div class="small">
                <div class="fw-semibold" id="spName"></div>
                <div class="text-muted" id="spText"></div>
            </div>
            <button type="button" class="btn-close btn-close-sm ms-auto" aria-label="Close" onclick="document.getElementById('socialProofToast').classList.add('d-none')"></button>
        </div>
    </div>
</div>
<script>
(function() {
    var toast = document.getElementById('socialProofToast');
    if (!toast) return;
    var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '';
    var labels = {
        tour: '<?= t('baru saja memesan paket tour') ?>',
        hotel: '<?= t('baru saja memesan hotel') ?>',
        ago_min: '<?= t(':m menit lalu') ?>',
        ago_now: '<?= t('baru saja') ?>'
    };
    function relTime(when) {
        var diff = Math.max(0, Math.floor((Date.now() - new Date(when.replace(' ', 'T')).getTime()) / 60000));
        if (diff < 1) return labels.ago_now;
        return labels.ago_min.replace(':m', diff);
    }
    function show(items, i) {
        if (!items.length) return;
        var it = items[i % items.length];
        document.getElementById('spName').textContent = it.name;
        document.getElementById('spText').textContent = labels[it.type] + ' · ' + relTime(it.when);
        toast.classList.remove('d-none');
        toast.setAttribute('data-sp-visible', '1');
        setTimeout(function() {
            toast.classList.add('d-none');
            toast.removeAttribute('data-sp-visible');
            setTimeout(function() { show(items, i + 1); }, 12000);
        }, 6000);
    }
    fetch(baseUrl + '/social-proof-ajax.php?limit=8')
        .then(function(r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function(d) {
            if (d && d.success && Array.isArray(d.items) && d.items.length > 0) {
                setTimeout(function() { show(d.items, 0); }, 4000);
            }
        })
        .catch(function() { /* sad path: senyap */ });
})();
</script>
