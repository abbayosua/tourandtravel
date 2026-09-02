<?php
/**
 * renderPagination — Klook-style pagination
 *
 * @param int    $current Current page number
 * @param int    $last    Last page number
 * @param string $baseUrl Base URL with query params (excluding page)
 */
function renderPagination($current, $last, $baseUrl = null) {
    if ($last <= 1) return;
    if ($baseUrl === null) {
        $baseUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__']));
    } else {
        $baseUrl = str_replace('__PAGE__', '__PAGE__', $baseUrl);
    }
    $makeUrl = function($p) use ($baseUrl) {
        return str_replace('__PAGE__', $p, $baseUrl);
    };
    ?>
    <nav class="mt-4">
        <ul class="pagination pagination-sm justify-content-center klook-pagination">
            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $makeUrl($current - 1) ?>">«</a>
            </li>
            <?php for ($p = max(1, $current - 2); $p <= min($last, $current + 2); $p++): ?>
            <li class="page-item <?= $p === $current ? 'active' : '' ?>">
                <a class="page-link" href="<?= $makeUrl($p) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $current >= $last ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $makeUrl($current + 1) ?>">»</a>
            </li>
        </ul>
    </nav>
    <?php
}