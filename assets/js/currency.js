/**
 * Currency Switcher JS
 * Converts prices from any source currency to the user's selected currency
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
            localStorage.setItem('currency_rates', JSON.stringify(this.rates));
            localStorage.setItem('currency_rates_time', Date.now());
        } catch (e) {
            const cached = localStorage.getItem('currency_rates');
            if (cached) {
                this.rates = JSON.parse(cached);
            } else {
                this.rates = { IDR: 17000, SGD: 1.45, USD: 1.08 };
            }
        }
    },

    /**
     * Convert amount from one currency to another using EUR as pivot
     */
    convert(amount, fromCurrency, toCurrency) {
        if (fromCurrency === toCurrency || !this.rates) return amount;
        const eurAmount = amount / (this.rates[fromCurrency] || 1);
        return eurAmount * (this.rates[toCurrency] || 1);
    },

    format(amount, currency) {
        const cfg = this.currencies[currency] || this.currencies.IDR;
        const formatted = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: cfg.decimals,
            maximumFractionDigits: cfg.decimals
        }).format(amount);
        return cfg.position === 'before'
            ? cfg.symbol + ' ' + formatted
            : formatted + ' ' + cfg.symbol;
    },

    render() {
        document.querySelectorAll('.currency-price').forEach(el => {
            const price = parseFloat(el.dataset.price);
            const fromCurrency = el.dataset.fromCurrency || 'IDR';
            if (!isNaN(price)) {
                const converted = this.convert(price, fromCurrency, this.currentCurrency);
                el.textContent = this.format(converted, this.currentCurrency);
            }
        });
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
