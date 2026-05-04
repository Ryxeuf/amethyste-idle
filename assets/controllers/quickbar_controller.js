import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'quickbar_slots';
const SLOT_COUNT = 6;

export default class extends Controller {
    static targets = ['slot', 'picker', 'pickerList'];
    static values = {
        labels: { type: Object, default: {} },
    };

    _label(key, fallback) {
        const value = this.labelsValue?.[key];
        return typeof value === 'string' && value.length > 0 ? value : fallback;
    }

    connect() {
        this._slots = this._loadSlots();
        this._items = [];
        this._pickerOpen = false;
        this._pickerSlotIndex = null;
        this._cooldowns = new Array(SLOT_COUNT).fill(false);

        this._onKeyDown = (e) => this._handleKeyDown(e);
        document.addEventListener('keydown', this._onKeyDown);

        this._fetchItems();
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeyDown);
    }

    // --- Public actions ---

    slotClick(event) {
        const index = parseInt(event.currentTarget.dataset.slotIndex, 10);
        const slot = this._slots[index];

        if (slot && slot.slug) {
            this._useSlot(index);
        } else {
            this._openPicker(index);
        }
    }

    slotRightClick(event) {
        event.preventDefault();
        const index = parseInt(event.currentTarget.dataset.slotIndex, 10);
        if (this._slots[index] && this._slots[index].slug) {
            this._clearSlot(index);
        } else {
            this._openPicker(index);
        }
    }

    pickItem(event) {
        const slug = event.currentTarget.dataset.itemSlug;
        const item = this._items.find(i => i.slug === slug);
        if (item && this._pickerSlotIndex !== null) {
            this._slots[this._pickerSlotIndex] = {
                slug: item.slug,
                name: item.name,
                id: item.id,
            };
            this._saveSlots();
            this._render();
        }
        this._closePicker();
    }

    closePicker() {
        this._closePicker();
    }

    // --- Keyboard ---

    _handleKeyDown(e) {
        // Only respond to 1-6 keys when not typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;

        const num = parseInt(e.key, 10);
        if (num >= 1 && num <= SLOT_COUNT) {
            e.preventDefault();
            this._useSlot(num - 1);
        }
    }

    // --- Item usage ---

    async _useSlot(index) {
        const slot = this._slots[index];
        if (!slot || !slot.slug || this._cooldowns[index]) return;

        const item = this._items.find(i => i.slug === slot.slug);
        if (!item || !item.usable || item.quantity <= 0) {
            this._showToast(this._label('item_unavailable', 'Objet indisponible'));
            return;
        }

        this._cooldowns[index] = true;
        this._renderSlotCooldown(index, true);

        try {
            const resp = await fetch(`/api/quickbar/use/${item.id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            const data = await resp.json();

            if (data.success) {
                this._showToast(data.message);
                // Update local item data
                item.quantity = data.remaining;
                if (data.newId) {
                    item.id = data.newId;
                    slot.id = data.newId;
                }
                if (data.remaining <= 0) {
                    this._clearSlot(index);
                    this._items = this._items.filter(i => i.slug !== slot.slug);
                }
                this._render();
            } else {
                this._showToast(data.message || this._label('error', 'Erreur'));
            }
        } catch {
            this._showToast(this._label('network_error', 'Erreur réseau'));
        }

        setTimeout(() => {
            this._cooldowns[index] = false;
            this._renderSlotCooldown(index, false);
        }, 1000);
    }

    // --- Picker ---

    _openPicker(index) {
        this._pickerSlotIndex = index;
        this._fetchItems().then(() => {
            this._renderPicker();
            this.pickerTarget.classList.remove('hidden');
            this._pickerOpen = true;
        });
    }

    _closePicker() {
        this.pickerTarget.classList.add('hidden');
        this._pickerOpen = false;
        this._pickerSlotIndex = null;
    }

    _renderPicker() {
        const list = this.pickerListTarget;
        list.innerHTML = '';

        const usableItems = this._items.filter(i => i.usable && i.quantity > 0);

        if (usableItems.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'text-gray-500 text-xs text-center py-3';
            empty.textContent = this._label('empty_picker', 'Aucun consommable disponible');
            list.appendChild(empty);
            return;
        }

        for (const item of usableItems) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full flex items-center gap-2 px-3 py-2 text-left hover:bg-gray-700/50 rounded-lg transition-colors';
            btn.dataset.action = 'click->quickbar#pickItem';
            btn.dataset.itemSlug = item.slug;
            btn.innerHTML = `
                <span class="w-7 h-7 rounded bg-purple-900/40 flex items-center justify-center text-purple-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                </span>
                <span class="flex-1 min-w-0">
                    <span class="text-xs text-white truncate block">${this._escapeHtml(item.name)}</span>
                    <span class="text-[10px] text-gray-500">${this._escapeHtml(item.description || '')}</span>
                </span>
                <span class="text-[10px] bg-purple-900/30 text-purple-400 px-1.5 py-0.5 rounded shrink-0">x${item.quantity}</span>
            `;
            list.appendChild(btn);
        }
    }

    // --- Data ---

    async _fetchItems() {
        try {
            const resp = await fetch('/api/quickbar/items');
            this._items = await resp.json();
            this._syncSlots();
            this._render();
        } catch {
            this._items = [];
        }
    }

    _syncSlots() {
        // Update slot IDs and remove slots for items no longer in inventory
        for (let i = 0; i < SLOT_COUNT; i++) {
            const slot = this._slots[i];
            if (!slot || !slot.slug) continue;

            const item = this._items.find(it => it.slug === slot.slug);
            if (!item || item.quantity <= 0) {
                this._slots[i] = null;
            } else {
                slot.id = item.id;
                slot.name = item.name;
            }
        }
        this._saveSlots();
    }

    // --- Rendering ---

    _render() {
        this.slotTargets.forEach((el, index) => {
            const slot = this._slots[index];
            const keyHint = el.querySelector('[data-key]');
            const nameEl = el.querySelector('[data-name]');
            const qtyEl = el.querySelector('[data-qty]');

            if (keyHint) keyHint.textContent = index + 1;

            if (slot && slot.slug) {
                const item = this._items.find(i => i.slug === slot.slug);
                const qty = item ? item.quantity : 0;
                nameEl.textContent = this._truncate(slot.name, 8);
                nameEl.title = slot.name;
                qtyEl.textContent = qty > 0 ? `x${qty}` : '';
                el.classList.toggle('opacity-40', qty <= 0);
                el.classList.remove('border-gray-700/50');
                el.classList.add('border-purple-500/30');
            } else {
                nameEl.textContent = '';
                nameEl.title = '';
                qtyEl.textContent = '';
                el.classList.remove('opacity-40', 'border-purple-500/30');
                el.classList.add('border-gray-700/50');
            }
        });
    }

    _renderSlotCooldown(index, active) {
        const el = this.slotTargets[index];
        if (!el) return;
        el.classList.toggle('pointer-events-none', active);
        el.classList.toggle('animate-pulse', active);
    }

    // --- Storage ---

    _loadSlots() {
        try {
            const data = JSON.parse(localStorage.getItem(STORAGE_KEY));
            if (Array.isArray(data) && data.length === SLOT_COUNT) return data;
        } catch { /* ignore */ }
        return new Array(SLOT_COUNT).fill(null);
    }

    _saveSlots() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this._slots));
    }

    _clearSlot(index) {
        this._slots[index] = null;
        this._saveSlots();
        this._render();
    }

    // --- Helpers ---

    _truncate(str, max) {
        return str && str.length > max ? str.slice(0, max) + '…' : (str || '');
    }

    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    _showToast(message) {
        document.dispatchEvent(new CustomEvent('toast:show', { detail: { message, type: 'info' } }));
    }
}
