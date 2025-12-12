//scroll-triggered reveal animations for elements with .reveal-on-scroll
document.addEventListener('DOMContentLoaded', () => {
    //all elements that should fade/slide in on scroll
    const revealEls = document.querySelectorAll('.reveal-on-scroll');

    //if browser does not support intersectionobserver, just show everything
    if (!('IntersectionObserver' in window)) {
        revealEls.forEach(el => el.classList.add('visible'));
        return;
    }

    //observer that adds .visible when an element comes into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                el.classList.add('visible');
                //we do not need to watch this element anymore
                observer.unobserve(el);
            }
        });
    }, {
        threshold: 0.12 //trigger when roughly 12% of the element is visible
    });

    //observe each element and add a small staggered delay
    revealEls.forEach((el, index) => {
        //only set delay if one is not already defined inline
        if (!el.style.transitionDelay) {
            const delay = Math.min(index * 80, 600); //cap delay at 0.6s
            el.style.transitionDelay = delay + 'ms';
        }
        observer.observe(el);
    });
});

//hero section entrance animation on initial page load
document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.hero-modern');
    if (!hero) return;

    //small delay so the hero fade-in feels intentional
    setTimeout(() => {
        hero.classList.add('hero-visible');
    }, 150);
});
