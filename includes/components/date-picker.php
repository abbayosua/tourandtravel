<?php
/**
 * Date Picker — SATU komponen tanggal reusable untuk SEMUA halaman (flatpickr).
 *
 * Dipakai via:
 *   require_once 'includes/components/date-picker.php';   // atau __DIR__ . '/...'
 *   renderDatePicker(['name' => 'date', 'value' => $date, 'min' => 'today']);
 *
 * Halaman publik (footer-shared.php) sudah memuat flatpickr global.
 * Halaman admin (tanpa footer-shared) wajib panggil dpAssets() sekali.
 *
 * Opsi:
 *   name, id (auto), value, min ('today'|Y-m-d|''), max, placeholder,
 *   label, required (bool), cls (default 'form-control'),
 *   months (default 1), inline (bool, default false),
 *   prices ([['date'=>..,'price'=>..]] untuk pewarnaan hari),
 *   priceBase (angka|'avg'|null — batas hijau/merah + label hasil),
 *   priceCurrency (default 'IDR'), resultId (elemen ringkasan harga),
 *   resultBaseLabel (label bila tanggal tak ada di prices),
 *   onChange (nama fungsi JS global: fn(dateStr, inputEl)),
 *   attrs (atribut tambahan assoc), bare (tanpa wrapper/label),
 *   mode 'single' (default) | 'range' (butuh startName/endName [+ids/values]).
 */
function dpAssets(): void {
    if (defined('DP_ASSETS_DONE')) return;
    define('DP_ASSETS_DONE', true);
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">' . "\n";
    echo '<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>' . "\n";
}

function renderDatePicker(array $o = []): void {
    static $n = 0;
    static $initDone = false;
    $n++;
    $mode = $o['mode'] ?? 'single';
    if (!in_array($mode, ['single', 'range'], true)) $mode = 'single';
    $isRange = $mode === 'range';
    $name = $o['name'] ?? 'date';
    $id = $o['id'] ?? ('dp-' . preg_replace('/[^a-z0-9]+/i', '-', (string)$name) . '-' . $n);
    $cls = $o['cls'] ?? 'form-control';
    $attrs = $o['attrs'] ?? [];
    $attrStr = '';
    foreach ($attrs as $k => $v) $attrStr .= ' ' . e((string)$k) . '="' . e((string)$v) . '"';

    if ($isRange) {
        $startName = $o['startName'] ?? 'checkin';
        $endName = $o['endName'] ?? 'checkout';
        $startId = $o['startId'] ?? ($id . '-start');
        $endId = $o['endId'] ?? ($id . '-end');
        $startVal = $o['startValue'] ?? '';
        $endVal = $o['endValue'] ?? '';
        $val = ($startVal !== '' && $endVal !== '') ? $startVal . ' to ' . $endVal : '';
    } else {
        $val = $o['value'] ?? '';
    }
    $data = [
        'dp-mode' => $mode,
        'dp-months' => (string)(int)($o['months'] ?? 1),
    ];
    if (!empty($o['inline'])) $data['dp-inline'] = '1';
    if (isset($o['min']) && $o['min'] !== '') $data['dp-min'] = (string)$o['min'];
    if (isset($o['max']) && $o['max'] !== '') $data['dp-max'] = (string)$o['max'];
    if (!empty($o['prices'])) $data['dp-prices'] = json_encode(array_values($o['prices']));
    if (isset($o['priceBase']) && $o['priceBase'] !== null && $o['priceBase'] !== '') $data['dp-base'] = (string)$o['priceBase'];
    if (!empty($o['priceCurrency'])) $data['dp-currency'] = (string)$o['priceCurrency'];
    if (!empty($o['resultId'])) $data['dp-result'] = (string)$o['resultId'];
    if (!empty($o['resultBaseLabel'])) $data['dp-result-base'] = (string)$o['resultBaseLabel'];
    if (!empty($o['onChange'])) $data['dp-onchange'] = (string)$o['onChange'];
    if ($isRange) {
        $data['dp-start'] = $startId;
        $data['dp-end'] = $endId;
    }
    $dataStr = '';
    foreach ($data as $k => $v) $dataStr .= ' data-' . $k . '="' . e($v) . '"';
    $fbMin = '';
    $fbMax = '';
    if (!empty($o['min'])) { $m = $o['min'] === 'today' ? date('Y-m-d') : (string)$o['min']; if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $m)) $fbMin = ' min="' . e($m) . '"'; }
    if (!empty($o['max'])) { $m = (string)$o['max']; if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $m)) $fbMax = ' max="' . e($m) . '"'; }

    $input = '<input type="text"'
        . (($isRange || !empty($o['noName'])) ? '' : ' name="' . e((string)$name) . '"')
        . ' id="' . e($id) . '"'
        . ' class="' . e(trim($cls . ' dp-flat')) . '"'
        . ' value="' . e((string)$val) . '"'
        . ' placeholder="' . e((string)($o['placeholder'] ?? t('Pilih tanggal'))) . '"'
        . ' autocomplete="off"'
        . (!empty($o['required']) ? ' required' : '')
        . $fbMin . $fbMax
        . $attrStr . $dataStr . '>';
    if ($isRange) {
        $input .= '<input type="hidden" name="' . e((string)$startName) . '" id="' . e($startId) . '" value="' . e((string)$startVal) . '">'
            . '<input type="hidden" name="' . e((string)$endName) . '" id="' . e($endId) . '" value="' . e((string)$endVal) . '">';
    }
    if (!empty($o['bare'])) {
        echo $input;
    } else {
        echo '<div class="dp-wrap">';
        if (!empty($o['label'])) echo '<label class="form-label small fw-semibold mb-1" for="' . e($id) . '">' . e((string)$o['label']) . '</label>';
        echo $input . '</div>';
    }
    if ($initDone) return;
    $initDone = true;
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function dpFmt(v, cur) {
        var sym = { IDR: 'Rp', SGD: 'S$', USD: '$' }[cur] || cur || '';
        var dec = cur === 'IDR' ? 0 : 2;
        var loc = (window.I18N && String(window.I18N.locale || '').indexOf('en') === 0) ? 'en-US' : 'id-ID';
        try { return sym + ' ' + new Intl.NumberFormat(loc, { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v); }
        catch (e) { return sym + ' ' + v; }
    }
    function dpBind(el) {
        if (!el || el.dataset.dpBound) return;
        el.dataset.dpBound = '1';
        if (typeof flatpickr === 'undefined') { el.type = 'date'; return; }
        var prices = [];
        try { prices = JSON.parse(el.getAttribute('data-dp-prices') || '[]'); } catch (e) { prices = []; }
        var map = {};
        prices.forEach(function (r) { if (r && r.date) map[r.date] = parseFloat(r.price); });
        var baseAttr = el.getAttribute('data-dp-base');
        var baseNum = NaN;
        if (baseAttr === 'avg' && prices.length) {
            var s = 0; prices.forEach(function (r) { s += parseFloat(r.price) || 0; });
            baseNum = s / prices.length;
        } else if (baseAttr !== null && baseAttr !== '') { baseNum = parseFloat(baseAttr); }
        var cur = el.getAttribute('data-dp-currency') || 'IDR';
        var resultEl = null;
        var rid = el.getAttribute('data-dp-result');
        if (rid) resultEl = document.getElementById(rid);
        var baseLabel = el.getAttribute('data-dp-result-base') || '';
        var cbName = el.getAttribute('data-dp-onchange');
        var isRange = el.getAttribute('data-dp-mode') === 'range';
        function paintDay(dObj, dStr, fp, dayElem) {
            if (!prices.length || !dayElem.dateObj) return;
            var k = dayElem.dateObj.getFullYear() + '-' + String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dayElem.dateObj.getDate()).padStart(2, '0');
            if (k in map) {
                dayElem.style.color = (!isNaN(baseNum) && map[k] > baseNum) ? '#dc3545' : '#198754';
                dayElem.title = dpFmt(map[k], cur);
            }
        }
        function fire(dateStr, sel) {
            if (resultEl) {
                if (dateStr && (dateStr in map)) { resultEl.textContent = dpFmt(map[dateStr], cur); }
                else if (dateStr && baseLabel && !isNaN(baseNum)) { resultEl.textContent = baseLabel + ' · ' + dpFmt(baseNum, cur); }
                else if (dateStr) { resultEl.textContent = dateStr; }
                else { resultEl.textContent = ''; }
            }
            if (cbName && typeof window[cbName] === 'function') {
                try { window[cbName](dateStr, el, sel || []); } catch (e) {}
            }
            try { el.dispatchEvent(new CustomEvent('dp:change', { bubbles: true, detail: { date: dateStr, input: el } })); } catch (e) {}
        }
        var opts = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            showMonths: parseInt(el.getAttribute('data-dp-months') || '1', 10) || 1,
            onDayCreate: paintDay
        };
        var mn = el.getAttribute('data-dp-min'), mx = el.getAttribute('data-dp-max');
        if (mn) opts.minDate = mn;
        if (mx) opts.maxDate = mx;
        if (el.getAttribute('data-dp-inline') === '1') { opts.inline = true; }
        if (isRange) {
            opts.mode = 'range';
            opts.onChange = function (sel) {
                var s = document.getElementById(el.getAttribute('data-dp-start'));
                var e2 = document.getElementById(el.getAttribute('data-dp-end'));
                if (sel.length === 2) {
                    var a = flatpickr.formatDate(sel[0], 'Y-m-d'), b = flatpickr.formatDate(sel[1], 'Y-m-d');
                    if (s) s.value = a;
                    if (e2) e2.value = b;
                    fire(a, sel);
                }
            };
        } else {
            opts.onChange = function (sel, dateStr) { fire(dateStr, sel); };
        }
        flatpickr(el, opts);
    }
    document.querySelectorAll('.dp-flat').forEach(dpBind);
    if (typeof MutationObserver !== 'undefined' && document.body) {
        new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                m.addedNodes.forEach(function (nd) {
                    if (nd.nodeType !== 1) return;
                    if (nd.classList && nd.classList.contains('dp-flat')) dpBind(nd);
                    if (nd.querySelectorAll) nd.querySelectorAll('.dp-flat').forEach(dpBind);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }
});
</script>
    <?php
}
