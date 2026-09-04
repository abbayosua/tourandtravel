/**
 * i18n.js — helper terjemahan sisi browser.
 * window.I18N di-inject dari PHP (header*.php): { lang, locale, strings }
 * strings berisi key terjemahan yang dibutuhkan JS (PHP mem-filter key yang terdaftar).
 */
(function () {
    'use strict';
    if (!window.I18N) {
        window.I18N = { lang: 'id', locale: 'id_ID', strings: {} };
    }
    var cache = window.I18N.strings || {};

    /**
     * Terjemahkan key; fallback ke key bila tidak ada terjemahan.
     * Mendukung placeholder sederhana: I18N.t('ada :n paket', {n: 5}) -> "ada 5 paket"
     */
    window.I18N.t = function (key, params) {
        var s = Object.prototype.hasOwnProperty.call(cache, key) ? cache[key] : key;
        if (params) {
            Object.keys(params).forEach(function (k) {
                s = s.replace(new RegExp(':' + k, 'g'), params[k]);
            });
        }
        return s;
    };
})();
