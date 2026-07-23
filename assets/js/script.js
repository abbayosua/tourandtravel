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
