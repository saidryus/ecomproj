document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.hero-modern');
    if (!hero) return;

    // Small delay
    setTimeout(() => {
        hero.classList.add('hero-visible');
    }, 150);
});
