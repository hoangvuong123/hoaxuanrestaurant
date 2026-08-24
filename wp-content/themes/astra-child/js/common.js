/* ============================================
   ANIMATION -SCROLL
   ============================================ */
document.addEventListener('DOMContentLoaded', function () {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('ani');
            }
        });
    }, { threshold: 0.3 });

    const targets = document.querySelectorAll('.animation');
    targets.forEach(el => observer.observe(el));
});
