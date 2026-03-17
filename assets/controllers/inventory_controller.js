import { Controller } from '@hotwired/stimulus';

/**
 * Inventory controller: item detail tooltip (desktop) & bottom-sheet (mobile).
 *
 * Usage:
 *   <div data-controller="inventory">
 *     <div data-inventory-target="item"
 *          data-item-name="Épée de feu"
 *          data-item-desc="Une épée enflammée"
 *          data-item-rarity="rare"
 *          data-item-protection="12"
 *          data-item-level="5"
 *          data-item-element="fire"
 *          data-item-slot="main_weapon"
 *          data-item-effects="Brûle l'ennemi">
 *     </div>
 *     <!-- tooltip & sheet targets are auto-created -->
 *   </div>
 */
export default class extends Controller {
    static targets = ['item', 'tooltip', 'sheet', 'sheetBackdrop', 'tab', 'tabsNav', 'tabsWrapper'];

    connect() {
        this._createTooltip();
        this._createSheet();
        this._isMobile = window.matchMedia('(hover: none)').matches;
        this._onResize = () => {
            this._isMobile = window.matchMedia('(hover: none)').matches;
            this._updateFades();
        };
        window.addEventListener('resize', this._onResize);
        this._initScrollFades();
    }

    disconnect() {
        window.removeEventListener('resize', this._onResize);
        this._tooltip?.remove();
        this._sheet?.remove();
        this._backdrop?.remove();
    }

    // ---- Tab switching ----

    switchTab(event) {
        const tab = event.currentTarget;
        this.tabTargets.forEach(t => t.classList.remove('inv-tab--active'));
        tab.classList.add('inv-tab--active');
    }

    _initScrollFades() {
        if (!this.hasTabsNavTarget) return;
        this.hasTabsNavTarget && this.tabsNavTarget.addEventListener('scroll', () => this._updateFades(), { passive: true });
        setTimeout(() => this._updateFades(), 100);
    }

    _updateFades() {
        if (!this.hasTabsWrapperTarget || !this.hasTabsNavTarget) return;
        const scroller = this.tabsNavTarget;
        const wrapper = this.tabsWrapperTarget;
        const sl = scroller.scrollLeft;
        const maxScroll = scroller.scrollWidth - scroller.clientWidth;
        wrapper.classList.toggle('scroll-left', sl > 8);
        wrapper.classList.toggle('scroll-right', sl < maxScroll - 8);
    }

    // ---- Tooltip (desktop hover) ----

    _createTooltip() {
        const el = document.createElement('div');
        el.className = 'inv-tooltip';
        el.setAttribute('data-inventory-target', 'tooltip');
        el.innerHTML = `
            <div class="inv-tooltip-header">
                <span class="inv-tooltip-name"></span>
                <span class="inv-tooltip-rarity"></span>
            </div>
            <div class="inv-tooltip-slot"></div>
            <div class="inv-tooltip-desc"></div>
            <div class="inv-tooltip-stats"></div>
            <div class="inv-tooltip-effects"></div>
        `;
        this.element.appendChild(el);
        this._tooltip = el;
    }

    // ---- Bottom Sheet (mobile tap) ----

    _createSheet() {
        // Backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'inv-sheet-backdrop';
        backdrop.setAttribute('data-inventory-target', 'sheetBackdrop');
        backdrop.addEventListener('click', () => this.closeSheet());

        // Sheet
        const sheet = document.createElement('div');
        sheet.className = 'inv-sheet';
        sheet.setAttribute('data-inventory-target', 'sheet');
        sheet.innerHTML = `
            <div class="inv-sheet-handle"></div>
            <div class="inv-sheet-header">
                <span class="inv-sheet-name"></span>
                <button class="inv-sheet-close" type="button">&times;</button>
            </div>
            <div class="inv-sheet-rarity"></div>
            <div class="inv-sheet-slot"></div>
            <div class="inv-sheet-desc"></div>
            <div class="inv-sheet-stats"></div>
            <div class="inv-sheet-effects"></div>
            <div class="inv-sheet-actions"></div>
        `;
        sheet.querySelector('.inv-sheet-close').addEventListener('click', () => this.closeSheet());

        this.element.appendChild(backdrop);
        this.element.appendChild(sheet);
        this._sheet = sheet;
        this._backdrop = backdrop;
    }

    // Called when an item target connects
    itemTargetConnected(el) {
        // Desktop: mouseenter/mouseleave
        el.addEventListener('mouseenter', (e) => this._showTooltip(e, el));
        el.addEventListener('mouseleave', () => this._hideTooltip());
        el.addEventListener('mousemove', (e) => this._moveTooltip(e));
        // Mobile: tap
        el.addEventListener('click', (e) => {
            if (this._isMobile) {
                e.preventDefault();
                e.stopPropagation();
                this._showSheet(el);
            }
        });
        // Make it look tappable
        el.style.cursor = 'pointer';
    }

    // ---- Tooltip logic ----

    _showTooltip(event, el) {
        if (this._isMobile) return;
        const data = this._extractData(el);
        if (!data.name) return;

        this._fillTooltip(data);
        this._tooltip.classList.add('inv-tooltip--visible');
        this._moveTooltip(event);
    }

    _moveTooltip(event) {
        if (!this._tooltip.classList.contains('inv-tooltip--visible')) return;
        const pad = 12;
        const rect = this._tooltip.getBoundingClientRect();
        let x = event.clientX + pad;
        let y = event.clientY + pad;

        // Keep within viewport
        if (x + rect.width > window.innerWidth - pad) {
            x = event.clientX - rect.width - pad;
        }
        if (y + rect.height > window.innerHeight - pad) {
            y = event.clientY - rect.height - pad;
        }

        this._tooltip.style.left = x + 'px';
        this._tooltip.style.top = y + 'px';
    }

    _hideTooltip() {
        this._tooltip.classList.remove('inv-tooltip--visible');
    }

    _fillTooltip(data) {
        const t = this._tooltip;
        t.querySelector('.inv-tooltip-name').textContent = data.name;

        const rarityEl = t.querySelector('.inv-tooltip-rarity');
        if (data.rarity) {
            rarityEl.textContent = this._rarityLabel(data.rarity);
            rarityEl.className = 'inv-tooltip-rarity inv-tooltip-rarity--' + data.rarity;
            rarityEl.style.display = '';
        } else {
            rarityEl.style.display = 'none';
        }

        const slotEl = t.querySelector('.inv-tooltip-slot');
        if (data.slot) {
            slotEl.textContent = this._slotLabel(data.slot);
            slotEl.style.display = '';
        } else {
            slotEl.style.display = 'none';
        }

        const descEl = t.querySelector('.inv-tooltip-desc');
        if (data.desc) {
            descEl.textContent = data.desc;
            descEl.style.display = '';
        } else {
            descEl.style.display = 'none';
        }

        const statsEl = t.querySelector('.inv-tooltip-stats');
        const stats = [];
        if (data.protection && data.protection !== '0') stats.push('+' + data.protection + ' DEF');
        if (data.level) stats.push('Niveau ' + data.level);
        if (data.element && data.element !== 'none') stats.push(this._elementLabel(data.element));
        if (stats.length) {
            statsEl.innerHTML = stats.map(s => '<span>' + s + '</span>').join('');
            statsEl.style.display = '';
        } else {
            statsEl.style.display = 'none';
        }

        const fxEl = t.querySelector('.inv-tooltip-effects');
        if (data.effects) {
            fxEl.textContent = data.effects;
            fxEl.style.display = '';
        } else {
            fxEl.style.display = 'none';
        }
    }

    // ---- Bottom sheet logic ----

    _showSheet(el) {
        const data = this._extractData(el);
        if (!data.name) return;

        const s = this._sheet;
        s.querySelector('.inv-sheet-name').textContent = data.name;

        const rarityEl = s.querySelector('.inv-sheet-rarity');
        if (data.rarity) {
            rarityEl.textContent = this._rarityLabel(data.rarity);
            rarityEl.className = 'inv-sheet-rarity inv-sheet-rarity--' + data.rarity;
            rarityEl.style.display = '';
        } else {
            rarityEl.style.display = 'none';
        }

        const slotEl = s.querySelector('.inv-sheet-slot');
        if (data.slot) {
            slotEl.textContent = this._slotLabel(data.slot);
            slotEl.style.display = '';
        } else {
            slotEl.style.display = 'none';
        }

        const descEl = s.querySelector('.inv-sheet-desc');
        if (data.desc) {
            descEl.textContent = data.desc;
            descEl.style.display = '';
        } else {
            descEl.style.display = 'none';
        }

        const statsEl = s.querySelector('.inv-sheet-stats');
        const stats = [];
        if (data.protection && data.protection !== '0') stats.push('<span class="inv-sheet-stat"><span class="inv-sheet-stat-val text-blue-400">+' + data.protection + '</span> DEF</span>');
        if (data.level) stats.push('<span class="inv-sheet-stat"><span class="inv-sheet-stat-val text-gray-300">Niv.' + data.level + '</span></span>');
        if (data.element && data.element !== 'none') stats.push('<span class="inv-sheet-stat"><span class="inv-sheet-stat-val">' + this._elementLabel(data.element) + '</span></span>');
        if (stats.length) {
            statsEl.innerHTML = stats.join('');
            statsEl.style.display = '';
        } else {
            statsEl.style.display = 'none';
        }

        const fxEl = s.querySelector('.inv-sheet-effects');
        if (data.effects) {
            fxEl.textContent = data.effects;
            fxEl.style.display = '';
        } else {
            fxEl.style.display = 'none';
        }

        // Copy action buttons from the item element
        const actionsEl = s.querySelector('.inv-sheet-actions');
        actionsEl.innerHTML = '';
        const forms = el.querySelectorAll('form');
        forms.forEach(f => {
            const clone = f.cloneNode(true);
            // Make buttons full width inside the sheet
            const btn = clone.querySelector('button');
            if (btn) {
                btn.className = 'inv-sheet-action-btn';
            }
            actionsEl.appendChild(clone);
        });

        // Show
        this._backdrop.classList.add('inv-sheet-backdrop--visible');
        s.classList.add('inv-sheet--visible');
        document.body.style.overflow = 'hidden';
    }

    closeSheet() {
        this._sheet.classList.remove('inv-sheet--visible');
        this._backdrop.classList.remove('inv-sheet-backdrop--visible');
        document.body.style.overflow = '';
    }

    // ---- Helpers ----

    _extractData(el) {
        return {
            name: el.dataset.itemName || '',
            desc: el.dataset.itemDesc || '',
            rarity: el.dataset.itemRarity || '',
            protection: el.dataset.itemProtection || '',
            level: el.dataset.itemLevel || '',
            element: el.dataset.itemElement || '',
            slot: el.dataset.itemSlot || '',
            effects: el.dataset.itemEffects || '',
        };
    }

    _rarityLabel(r) {
        const map = { legendary: 'Légendaire', epic: 'Épique', rare: 'Rare', uncommon: 'Peu commun', common: 'Commun' };
        return map[r] || r;
    }

    _slotLabel(s) {
        const map = {
            head: 'Tête', neck: 'Cou', chest: 'Torse', shoulder: 'Épaules',
            hand: 'Mains', main_weapon: 'Arme principale', side_weapon: 'Arme secondaire',
            belt: 'Ceinture', leg: 'Jambes', foot: 'Pieds',
            ring_1: 'Anneau 1', ring_2: 'Anneau 2',
        };
        return map[s] || s;
    }

    _elementLabel(e) {
        const map = { fire: 'Feu', water: 'Eau', earth: 'Terre', air: 'Air', light: 'Lumière', dark: 'Ténèbres' };
        return map[e] || e;
    }
}
