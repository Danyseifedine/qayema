import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ── GSAP + ScrollTrigger ──────────────────────────────────────
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

window.gsap = gsap;

// ── Welcome page scroll animations ───────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.hero')) return;

    // Hero sub-elements (above the fold — gentle entrance on load)
    gsap.from('.hero-strip', {
        opacity: 0, y: 18, duration: 0.7, ease: 'power2.out', delay: 0.3,
    });
    gsap.from('.hero-showcase', {
        opacity: 0, y: 28, duration: 0.8, ease: 'power2.out', delay: 0.15,
    });

    // Logos bar
    gsap.from('.logos-inner', {
        scrollTrigger: { trigger: '.logos', start: 'top 90%', once: true },
        opacity: 0, y: 16, duration: 0.6, ease: 'power2.out',
    });

    // Problem cards — staggered
    gsap.from('.problem-card', {
        scrollTrigger: { trigger: '.problem-grid', start: 'top 85%', once: true },
        opacity: 0, y: 28, duration: 0.55, ease: 'power2.out', stagger: 0.12,
    });

    // Section headings (all sections share .section-head)
    document.querySelectorAll('.section-head').forEach(el => {
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 88%', once: true },
            opacity: 0, y: 20, duration: 0.55, ease: 'power2.out',
        });
    });

    // Solution feat-rows — alternate slide direction left/right
    document.querySelectorAll('.feat-row').forEach(el => {
        const x = el.classList.contains('reverse') ? 40 : -40;
        gsap.from(el, {
            scrollTrigger: { trigger: el, start: 'top 86%', once: true },
            opacity: 0, x, duration: 0.65, ease: 'power2.out',
        });
    });

    // Features grid (x-for rendered — animate container as one unit)
    gsap.from('.features-grid', {
        scrollTrigger: { trigger: '.features-grid', start: 'top 85%', once: true },
        opacity: 0, y: 24, duration: 0.6, ease: 'power2.out',
    });

    // How steps (x-for rendered)
    gsap.from('.steps', {
        scrollTrigger: { trigger: '.steps', start: 'top 85%', once: true },
        opacity: 0, y: 24, duration: 0.6, ease: 'power2.out',
    });

    // Story / testimonial cards — staggered
    gsap.from('.quote-card', {
        scrollTrigger: { trigger: '.quote-grid', start: 'top 85%', once: true },
        opacity: 0, y: 24, duration: 0.55, ease: 'power2.out', stagger: 0.1,
    });

    // CTA section
    gsap.from('.cta-section', {
        scrollTrigger: { trigger: '.cta-section', start: 'top 88%', once: true },
        opacity: 0, y: 20, duration: 0.6, ease: 'power2.out',
    });
});
