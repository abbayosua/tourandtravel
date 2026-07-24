        </div><!-- /#adminContent -->
    </div><!-- /#adminWrapper -->
</div><!-- /.container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle sidebar
const toggleBtn = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
const body = document.body;

if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('collapsed');
        if (overlay) overlay.classList.toggle('show');
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
