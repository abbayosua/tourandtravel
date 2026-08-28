/**
 * Currency Switcher JS
 * Converts prices displayed with data-price-idr attribute
 */
const CurrencySwitcher = {
    currentCurrency: localStorage.getItem('currency') || 'IDR',
    rates: null,

    currencies: {
        IDR: { symbol: 'Rp', decimals: 0, position: 'before' },
        SGD: { symbol: 'S$', decimals: 2, position: 'before' },
        USD: { symbol: '$', decimals: 2, position: 'before' }
    },

    async init() {
        await this.fetchRates();
        this.render();
        this.bindEvents();
    },

    async fetchRates() {
        try {
            const resp = await fetch('https://api.frankfurter.app/latest?from=EUR&to=IDR,SGD,USD');
            const data = await resp.json();
            this.rates = data.rates;
            // Cache for 1 hour
            localStorage.setItem('currency_rates', JSON.stringify(this.rates));
            localStorage.setItem('currency_rates_time', Date.now());
        } catch (e) {
            // Try cache
            const cached = localStorage.getItem('currency_rates');
            if (cached) {
                this.rates = JSON.parse(cached);
            } else {
                this.rates = { IDR: 17000, SGD: 1.45, USD: 1.08 };
            }
        }
    },

    convert(amountIDR, toCurrency) {
        if (toCurrency === 'IDR' || !this.rates) return amountIDR;
        const eurAmount = amountIDR / (this.rates.IDR || 17000);
        return eurAmount * (this.rates[toCurrency] || 1);
    },

    format(amount, currency) {
        const cfg = this.currencies[currency] || this.currencies.IDR;
        const converted = this.convert(amount, currency);
        const formatted = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: cfg.decimals,
            maximumFractionDigits: cfg.decimals
        }).format(converted);
        return cfg.position === 'before'
            ? cfg.symbol + ' ' + formatted
            : formatted + ' ' + cfg.symbol;
    },

    render() {
        document.querySelectorAll('.currency-price').forEach(el => {
            const priceIDR = parseFloat(el.dataset.priceIdr);
            if (!isNaN(priceIDR)) {
                el.textContent = this.format(priceIDR, this.currentCurrency);
            }
        });
        // Update switcher buttons
        document.querySelectorAll('.currency-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.currency === this.currentCurrency);
        });
    },

    switchTo(currency) {
        if (this.currencies[currency]) {
            this.currentCurrency = currency;
            localStorage.setItem('currency', currency);
            this.render();
        }
    },

    bindEvents() {
        document.querySelectorAll('.currency-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTo(btn.dataset.currency);
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => CurrencySwitcher.init());
