/*
 * Homepage hero slider. Swiper + GSAP + Swiper's CSS are heavy and only ever
 * used on the landing page, so this whole module is dynamically imported (and
 * code-split into its own chunk) by app.js only when `#hero-swiper` is present.
 */
import { Swiper } from 'swiper';
import { Autoplay, EffectFade, Navigation } from 'swiper/modules';
import gsap from 'gsap';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';

export function initHeroSlider() {
    const el = document.getElementById('hero-swiper');

    if (!el) {
        return;
    }

    const slideContent = (slide) => ({
        title: slide.querySelector('.hero-slide-title'),
        subtitle: slide.querySelector('.hero-slide-subtitle'),
        cta: slide.querySelector('.hero-slide-cta'),
    });

    const animateSlideIn = (swiper) => {
        const { title, subtitle, cta } = slideContent(swiper.slides[swiper.activeIndex]);

        gsap.timeline()
            .fromTo(title, { y: 30, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: 'power2.out' })
            .fromTo(subtitle, { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5, ease: 'power2.out' }, '-=0.35')
            .fromTo(cta, { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.5, ease: 'power2.out' }, '-=0.3');
    };

    new Swiper(el, {
        modules: [Autoplay, EffectFade, Navigation],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        rewind: true,
        roundLengths: true,
        speed: 800,
        autoplay: { delay: 3000, disableOnInteraction: false },
        navigation: { nextEl: '.hero-nav-next', prevEl: '.hero-nav-prev' },
        on: {
            init: (instance) => animateSlideIn(instance),
            slideChangeTransitionStart: (instance) => animateSlideIn(instance),
        },
    });
}
