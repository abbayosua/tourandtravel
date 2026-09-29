        </div><!-- /#adminContent -->
    </div><!-- /#adminWrapper -->
</div><!-- /.container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Voyage theme toggle (localStorage, pola sama seperti navbar publik)
(function () {
    var btn = document.getElementById('adminThemeToggle');
    function icon() { return document.documentElement.getAttribute('data-theme') === 'dark' ? 'bi-sun' : 'bi-moon-stars'; }
    function paint() { if (btn) btn.innerHTML = '<i class="bi ' + icon() + '"></i>'; }
    paint();
    if (btn) btn.addEventListener('click', function () {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        var next = dark ? 'light' : 'dark';
        if (next === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        else document.documentElement.removeAttribute('data-theme');
        try { localStorage.setItem('theme', next); } catch (e) {}
        paint();
    });
})();
</script>
<script>
const toggleBtn = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
const body = document.body;

if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (window.innerWidth >= 768) {
            // Desktop: toggle icon-only mode
            sidebar.classList.toggle('icon-only');
        } else {
            // Mobile: toggle collapsed (off-canvas)
            sidebar.classList.toggle('collapsed');
            if (overlay) overlay.classList.toggle('show');
        }
    });

    // Click overlay to close sidebar on mobile
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.add('collapsed');
            overlay.classList.remove('show');
        });
    }

    // Close sidebar on Escape key (mobile)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && !sidebar.classList.contains('collapsed') && window.innerWidth < 768) {
            sidebar.classList.add('collapsed');
            if (overlay) overlay.classList.remove('show');
        }
    });
}
</script>
</body>
</html>
