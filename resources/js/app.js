import './bootstrap';

/* ─── Toasts ────────────────────────────────────────────────
 * Fire from anywhere:  window.toast('Saved!', 'success')
 * or from Livewire:    $this->dispatch('toast', message: '…', type: 'success')
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.store('toasts', {
        items: [],
        push(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 3200);
        },
        dismiss(id) {
            this.items = this.items.filter(t => t.id !== id);
        },
    });
});

window.toast = (message, type = 'success') => window.Alpine?.store('toasts')?.push(message, type);

window.addEventListener('toast', e => {
    const d = Array.isArray(e.detail) ? e.detail[0] : e.detail;
    window.toast(d?.message ?? String(d), d?.type ?? 'success');
});

/* ─── Clipboard ─────────────────────────────────────────── */
window.copyText = async (text, label = 'Link copied to clipboard') => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const ta = Object.assign(document.createElement('textarea'), { value: text });
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
    window.toast(label, 'success');
};

/* ─── Spotlight: cursor-follow glow on .spotlight cards ──── */
document.addEventListener('pointermove', e => {
    const el = e.target.closest?.('.spotlight');
    if (!el) return;
    const r = el.getBoundingClientRect();
    el.style.setProperty('--mx', `${e.clientX - r.left}px`);
    el.style.setProperty('--my', `${e.clientY - r.top}px`);
}, { passive: true });

/* ─── Confetti (tiny, dependency-free) ──────────────────── */
window.celebrate = (duration = 1800) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const canvas = document.createElement('canvas');
    Object.assign(canvas.style, { position: 'fixed', inset: 0, pointerEvents: 'none', zIndex: 9999 });
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const resize = () => {
        canvas.width = innerWidth * dpr;
        canvas.height = innerHeight * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();

    const colors = ['#8b5cf6', '#d946ef', '#f59e0b', '#10b981', '#38bdf8', '#f43f5e'];
    const pieces = Array.from({ length: 160 }, () => ({
        x: innerWidth / 2 + (Math.random() - 0.5) * 120,
        y: innerHeight * 0.35,
        vx: (Math.random() - 0.5) * 16,
        vy: Math.random() * -14 - 4,
        size: Math.random() * 7 + 4,
        rot: Math.random() * Math.PI,
        vr: (Math.random() - 0.5) * 0.3,
        color: colors[(Math.random() * colors.length) | 0],
        shape: Math.random() > 0.5 ? 'rect' : 'circle',
    }));

    const start = performance.now();
    const frame = now => {
        const t = now - start;
        ctx.clearRect(0, 0, innerWidth, innerHeight);
        ctx.globalAlpha = Math.max(0, 1 - Math.max(0, t - duration * 0.6) / (duration * 0.4));
        for (const p of pieces) {
            p.vy += 0.38;
            p.vx *= 0.99;
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.vr;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle = p.color;
            if (p.shape === 'rect') ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
            else { ctx.beginPath(); ctx.arc(0, 0, p.size / 3, 0, Math.PI * 2); ctx.fill(); }
            ctx.restore();
        }
        if (t < duration) requestAnimationFrame(frame);
        else canvas.remove();
    };
    requestAnimationFrame(frame);
};

/* ─── Builder drag & drop (SortableJS is loaded in the app layout) ───
 * Called from x-init so it survives Livewire morphs and wire:navigate.
 */
window.initFieldCanvas = (el, $wire) => {
    if (!window.Sortable || el._sortable) return;
    el._sortable = new window.Sortable(el, {
        group: { name: 'fields', pull: false, put: true },
        animation: 180,
        handle: '.drag-handle',
        draggable: '[data-field-id]',
        ghostClass: 'opacity-40',
        chosenClass: 'ring-2',
        onUpdate() {
            const ids = [...el.querySelectorAll('[data-field-id]')].map(n => n.dataset.fieldId);
            $wire.reorderFields(ids);
        },
        onAdd(evt) {
            // A palette tile was dropped in: drop the DOM clone and let Livewire render the real field
            const type = evt.item.dataset.type;
            const index = [...el.children].filter(n => n.dataset.fieldId || n === evt.item).indexOf(evt.item);
            evt.item.remove();
            $wire.addFieldAt(type, index);
        },
    });
};

window.initFieldPalette = el => {
    if (!window.Sortable || el._sortable) return;
    el._sortable = new window.Sortable(el, {
        group: { name: 'fields', pull: 'clone', put: false },
        sort: false,
        animation: 150,
        draggable: '[data-type]',
        // Put the original tile (with its Livewire/Alpine bindings) back in place of the bare clone
        onEnd(evt) {
            if (evt.to !== evt.from && evt.clone?.parentNode) evt.clone.replaceWith(evt.item);
        },
    });
};
