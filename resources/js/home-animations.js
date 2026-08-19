/*
 * Homepage scroll-reveal animations. GSAP is already loaded for the hero
 * slider, so this module reuses it and is dynamically imported by app.js only
 * when `[data-reveal]` sections exist on the page — it never ships elsewhere.
 *
 * Visibility is driven by IntersectionObserver rather than ScrollTrigger:
 * observer callbacks always fire once a section is on screen, so late layout
 * shifts (hero slider init, image loads) can never leave content stuck
 * hidden. GSAP only handles the tween itself.
 *
 * Motion language matches hero.js: subtle upward drift + fade, power2.out,
 * ~0.6s, small stagger. Sections opt in via markup only:
 *   data-reveal          on a <section> enables it
 *   data-reveal-item     animated as-is (headings, standalone blocks)
 *   data-reveal-children animates the element's direct children (card grids)
 *   data-float           gentle idle bob (app-banner phone mockup)
 * Everything is skipped for users who prefer reduced motion.
 */
import gsap from 'gsap';

export function initHomeAnimations() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        return;
    }

    const targetsBySection = new Map();

    document.querySelectorAll('[data-reveal]').forEach((section) => {
        const targets = [
            ...section.querySelectorAll('[data-reveal-item]'),
            ...[...section.querySelectorAll('[data-reveal-children]')].flatMap((group) => [...group.children]),
        ];

        if (targets.length) {
            // autoAlpha (opacity + visibility) keeps hidden items out of the a11y tree.
            gsap.set(targets, { autoAlpha: 0, y: 24 });
            targetsBySection.set(section, targets);
        }
    });

    if (targetsBySection.size) {
        const observer = new IntersectionObserver(
            (entries, self) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    self.unobserve(entry.target);

                    gsap.to(targetsBySection.get(entry.target), {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.6,
                        ease: 'power2.out',
                        stagger: 0.07,
                        clearProps: 'all', // hand hover transitions back to CSS once revealed
                    });
                });
            },
            { rootMargin: '0px 0px -10% 0px' },
        );

        targetsBySection.forEach((targets, section) => observer.observe(section));
    }

    document.querySelectorAll('[data-float]').forEach((element) => {
        gsap.to(element, {
            y: -10,
            duration: 3,
            ease: 'sine.inOut',
            yoyo: true,
            repeat: -1,
        });
    });
}
