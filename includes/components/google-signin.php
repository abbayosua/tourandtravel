<?php
/**
 * includes/components/google-signin.php — Fase 1: tombol "Login dengan Google".
 * Pakai Google Identity Services JS. Butuh GOOGLE_CLIENT_ID di config.php.
 * $info['dark']: bool (opsional) — styling gelap.
 * Render nothing jika GOOGLE_CLIENT_ID kosong (fitur off).
 */
$googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
$googleDark = !empty($info['dark']);
if ($googleClientId === ''):
?>
<div class="text-center my-3"><span class="text-muted small">— <?= t('atau') ?> —</span></div>
<?php else: ?>
<div class="my-3">
    <div id="g_id_signin"></div>
    <input type="hidden" id="google_credential">
</div>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
(function () {
    window.handleGoogleCredential = function (response) {
        var redirect = new URLSearchParams(location.search).get('redirect');
        fetch('<?= BASE_URL ?>/api/oauth-google.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential }),
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                location.href = redirect && redirect.indexOf('/') === 0 && redirect.indexOf('//') === -1 ? redirect : 'index.php';
            } else {
                alert(d.message || 'Login Google gagal');
            }
        })
        .catch(function () { alert('Login Google gagal'); });
    };
    window.initGoogleSignIn = function () {
        google.accounts.id.initialize({
            client_id: '<?= htmlspecialchars($googleClientId) ?>',
            callback: window.handleGoogleCredential
        });
        google.accounts.id.renderButton(document.getElementById('g_id_signin'), {
            theme: '<?= $googleDark ? 'filled_black' : 'outline' ?>',
            size: 'large',
            width: 320,
            text: '<?= t('Masuk dengan Google') ?>'
        });
    };
    document.addEventListener('DOMContentLoaded', function () {
        if (window.google && google.accounts && google.accounts.id) initGoogleSignIn();
        else window.addEventListener('load', function () { if (window.google && google.accounts && google.accounts.id) initGoogleSignIn(); });
    });
})();
</script>
<?php endif; ?>
