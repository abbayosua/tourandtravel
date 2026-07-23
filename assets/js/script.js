// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Auto-hide alert after 5 seconds
document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    }, 5000);
});

// Lazy-load hero video after page load
window.addEventListener('load', function () {
    var video = document.getElementById('heroVideo');
    if (!video) return;

    var source = document.createElement('source');
    source.src = 'backgroundvideo.mp4';
    source.type = 'video/mp4';
    video.appendChild(source);

    video.addEventListener('canplay', function () {
        video.classList.add('loaded');
        // Hilangkan background image statis setelah video siap
        var bg = document.querySelector('.hero-bg');
        if (bg) bg.style.opacity = '0';
        video.play().catch(function () {});
    });

    video.load();
});

// Autocomplete Search
function initSearchAutocomplete(inputId, dropdownId) {
    var input = document.getElementById(inputId);
    var dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    var debounceTimer;

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = this.value.trim();
        if (q.length < 2) {
            dropdown.classList.remove('show');
            dropdown.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function () {
            fetch('search-ajax.php?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.length) {
                        dropdown.classList.remove('show');
                        dropdown.innerHTML = '';
                        return;
                    }
                    var html = '';
                    data.forEach(function (item) {
                        var url, icon, label2;
                        if (item.type === 'category') {
                            url = 'tours.php?category=' + encodeURIComponent(item.label);
                            icon = 'bi-tag';
                            label2 = 'Kategori';
                        } else {
                            url = 'tour-detail.php?slug=' + item.slug;
                            icon = 'bi-geo-alt';
                            label2 = 'Mulai ' + (item.price ? 'Rp' + Number(item.price).toLocaleString('id-ID') : '-');
                        }
                        html += '<a href="' + url + '" class="search-item">' +
                            '<div class="search-icon bg-light text-primary"><i class="bi ' + icon + '"></i></div>' +
                            '<div class="flex-grow-1"><div class="fw-semibold small">' + escapeHtml(item.label) + '</div>' +
                            '<div class="text-muted" style="font-size: 11px;">' + label2 + '</div></div></a>';
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.add('show');
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.parentElement.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.remove('show');
    });
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function () {
    initSearchAutocomplete('heroSearch', 'heroSearchDropdown');
    initSearchAutocomplete('catalogSearch', 'catalogSearchDropdown');
    initSearchAutocomplete('navSearch', 'navSearchDropdown');
});

// Toggle navbar search on scroll
var navbar = document.querySelector('.sticky-top');
if (navbar) {
    var hero = document.querySelector('.hero-klook');
    window.addEventListener('scroll', function () {
        var scrollY = window.scrollY;
        var heroBottom = hero ? hero.offsetTop + hero.offsetHeight : 400;
        if (scrollY > heroBottom - 80) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    });
}
