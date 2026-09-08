<?php
/**
 * Widget live chat tawk.to — render hanya bila tawk_property_id tersimpan.
 * Dipanggil dari footer (sebelum </body>).
 */
$tawkPropertyId = getSetting('tawk_property_id', '');
if ($tawkPropertyId):
    $tawkWidgetId = getSetting('tawk_widget_id', 'default') ?: 'default';
?>
<script type="text/javascript" data-testid="tawk-script">
var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
(function(){
    var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
    s1.async = true;
    s1.src = 'https://embed.tawk.to/<?= e($tawkPropertyId) ?>/<?= e($tawkWidgetId) ?>';
    s1.charset = 'UTF-8';
    s1.setAttribute('crossorigin', '*');
    s0.parentNode.insertBefore(s1, s0);
})();
</script>
<?php endif; ?>
