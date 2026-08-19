/*
 * Drag-and-drop ordering for admin lists. SortableJS is only needed on the
 * handful of admin screens that carry `[data-sortable]`, so this module is
 * dynamically imported (its own chunk) by app.js only when such a list exists.
 *
 * Each sortable list carries data-sortable, data-sortable-url (POST endpoint)
 * and — for lists that reorder within a page — data-sortable-offset (the
 * pagination offset, so page 2's positions continue after page 1). Optional
 * data-sortable-type is forwarded in the body for scoped lists. Each row
 * carries data-id. On drop we persist the new order via fetch.
 */
import Sortable from 'sortablejs';

export function initSortables() {
    document.querySelectorAll('[data-sortable]').forEach((list) => {
        Sortable.create(list, {
            handle: '[data-drag-handle]',
            animation: 150,
            ghostClass: 'opacity-40',
            dragClass: 'shadow-lg',
            onEnd() {
                const ids = Array.from(list.querySelectorAll('[data-id]')).map((row) => row.dataset.id);
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const payload = { ids, offset: Number(list.dataset.sortableOffset) || 0 };

                if (list.dataset.sortableType) {
                    payload.type = list.dataset.sortableType;
                }

                fetch(list.dataset.sortableUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('save failed');
                        }
                        window.adminToast?.('success', 'Order saved.');
                    })
                    .catch(() => window.adminToast?.('error', "Couldn't save the new order. Please refresh."));
            },
        });
    });
}
