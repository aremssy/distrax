import { createIcons, icons } from 'lucide';

/*
 * Bundled, self-hosted replacement for the old render-blocking
 * `unpkg.com/lucide@latest` <script>. Loaded on demand (see app.js) only on
 * pages that actually render `[data-lucide]` icons, so it stays off the
 * critical path and out of admin bundles.
 *
 * We expose the same `window.lucide.createIcons()` surface the UMD build gave
 * us, because Blade partials and other modules call it after swapping in new
 * markup (live search, password toggles, mobile menu, etc.).
 */
window.lucide = {
    createIcons: (options = {}) => createIcons({ icons, ...options }),
};

window.lucide.createIcons();
