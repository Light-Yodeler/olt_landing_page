function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('mobileMenuBtn');
    if (menu) {
        const isHidden = menu.classList.toggle('hidden');
        if (btn) {
            btn.setAttribute('aria-expanded', (!isHidden).toString());
        }
    }
}

window.toggleMobileMenu = toggleMobileMenu;

let currentSlide = 0;
let slideInterval = null;

function showSlide(index) {
    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.slide-indicator');
    if (!slides.length) return;

    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });

    indicators.forEach((dot, i) => {
        if (i === index) {
            dot.classList.remove('w-3', 'bg-white/50');
            dot.classList.add('w-8', 'bg-amberGlow-400');
        } else {
            dot.classList.remove('w-8', 'bg-amberGlow-400');
            dot.classList.add('w-3', 'bg-white/50');
        }
    });

    currentSlide = index;
}

function nextSlide() {
    const slides = document.querySelectorAll('.hero-slide');
    if (!slides.length) return;
    const next = (currentSlide + 1) % slides.length;
    showSlide(next);
}

function setSlide(index) {
    showSlide(index);
    if (slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
}

window.setSlide = setSlide;

document.addEventListener('DOMContentLoaded', () => {
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length > 1) {
        slideInterval = setInterval(nextSlide, 5000);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const menu = document.getElementById('mobileMenu');
            if (menu && !menu.classList.contains('hidden')) {
                toggleMobileMenu();
            }
        }
    });
});
