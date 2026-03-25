import { Controller } from '@hotwired/stimulus';

/**
 * Materia slot controller: FF7-style orb tooltip (desktop) & bottom-sheet (mobile).
 *
 * Usage: data-controller="materia-slot" on the .materia-slots-bar container,
 *        data-materia-slot-target="orb" on each orb element.
 */
export default class extends Controller {
    static targets = ['orb'];

    connect() {
        this._isMobile = window.matchMedia('(hover: none)').matches;
        this._onResize = () => { this._isMobile = window.matchMedia('(hover: none)').matches; };
        window.addEventListener('resize', this._onResize);
        this._createTooltip();
        this._createSheet();

        // Close tooltip on outside click
        this._onDocClick = (e) => {
            if (this._tooltip && !this._tooltip.contains(e.target) && !e.target.closest('.materia-slot-orb')) {
                this._hideTooltip();
            }
        };
        document.addEventListener('click', this._onDocClick);
    }

    disconnect() {
        window.removeEventListener('resize', this._onResize);
        document.removeEventListener('click', this._onDocClick);
        this._tooltip?.remove();
        this._sheet?.remove();
        this._backdrop?.remove();
    }

    _createTooltip() {
        const el = document.createElement('div');
        el.className = 'materia-slot-tooltip';
        el.innerHTML = `
            <div class="materia-slot-tooltip__name"></div>
            <div class="materia-slot-tooltip__element"></div>
            <div class="flex items-center gap-1.5 text-[10px] text-gray-400">
                <span class="materia-slot-tooltip__level-label"></span>
                <span class="materia-slot-tooltip__xp-label"></span>
            </div>
            <div class="materia-slot-tooltip__xp-bar"><div class="materia-slot-tooltip__xp-fill"></div></div>
            <div class="materia-slot-tooltip__spell"></div>
            <div class="materia-slot-tooltip__bonuses"></div>
            <div class="materia-slot-tooltip__actions"></div>
        `;
        document.body.appendChild(el);
        this._tooltip = el;
    }

    _createSheet() {
        const backdrop = document.createElement('div');
        backdrop.className = 'materia-sheet-backdrop';
        backdrop.addEventListener('click', () => this._closeSheet());

        const sheet = document.createElement('div');
        sheet.className = 'materia-sheet';
        sheet.innerHTML = `
            <div class="materia-sheet__handle"></div>
            <div class="materia-slot-tooltip__name text-base mb-1"></div>
            <div class="materia-slot-tooltip__element"></div>
            <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-1">
                <span class="materia-slot-tooltip__level-label"></span>
                <span class="materia-slot-tooltip__xp-label"></span>
            </div>
            <div class="materia-slot-tooltip__xp-bar mt-1"><div class="materia-slot-tooltip__xp-fill"></div></div>
            <div class="materia-slot-tooltip__spell text-sm mt-2"></div>
            <div class="materia-slot-tooltip__bonuses mt-2"></div>
            <div class="materia-slot-tooltip__actions mt-3"></div>
        `;

        document.body.appendChild(backdrop);
        document.body.appendChild(sheet);
        this._sheet = sheet;
        this._backdrop = backdrop;
    }

    orbTargetConnected(el) {
        const isFilled = el.dataset.slotFilled === '1';

        if (isFilled) {
            // Prevent default click — we handle it ourselves
            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this._isMobile) {
                    this._showSheet(el);
                } else {
                    this._toggleTooltip(el, e);
                }
            });

            // Desktop hover shows tooltip
            el.addEventListener('mouseenter', (e) => {
                if (!this._isMobile && !this._tooltipPinned) {
                    this._showTooltip(el, e);
                }
            });
            el.addEventListener('mouseleave', () => {
                if (!this._tooltipPinned) {
                    this._hideTooltip();
                }
            });
        }
        // Empty slots use <a> tags — default navigation is fine
    }

    // ---- Tooltip (desktop) ----

    _toggleTooltip(el, event) {
        if (this._tooltipPinned && this._pinnedOrb === el) {
            this._hideTooltip();
            return;
        }
        this._showTooltip(el, event, true);
    }

    _showTooltip(el, event, pinned = false) {
        this._fillPanel(this._tooltip, el);
        this._tooltip.classList.add('materia-slot-tooltip--visible');
        this._tooltipPinned = pinned;
        this._pinnedOrb = pinned ? el : null;

        // Position near the orb
        const orbRect = el.getBoundingClientRect();
        const tipRect = this._tooltip.getBoundingClientRect();
        let x = orbRect.left + orbRect.width / 2 - tipRect.width / 2;
        let y = orbRect.top - tipRect.height - 8;

        // Keep within viewport
        if (y < 8) y = orbRect.bottom + 8;
        if (x < 8) x = 8;
        if (x + tipRect.width > window.innerWidth - 8) x = window.innerWidth - tipRect.width - 8;

        this._tooltip.style.left = x + 'px';
        this._tooltip.style.top = y + 'px';
    }

    _hideTooltip() {
        this._tooltip?.classList.remove('materia-slot-tooltip--visible');
        this._tooltipPinned = false;
        this._pinnedOrb = null;
    }

    // ---- Bottom sheet (mobile) ----

    _showSheet(el) {
        this._fillPanel(this._sheet, el);
        this._backdrop.classList.add('materia-sheet-backdrop--visible');
        this._sheet.classList.add('materia-sheet--visible');
        document.body.style.overflow = 'hidden';
    }

    _closeSheet() {
        this._sheet?.classList.remove('materia-sheet--visible');
        this._backdrop?.classList.remove('materia-sheet-backdrop--visible');
        document.body.style.overflow = '';
    }

    // ---- Fill panel content (shared for tooltip + sheet) ----

    _fillPanel(panel, el) {
        const d = el.dataset;
        const isFilled = d.slotFilled === '1';

        // Name
        panel.querySelector('.materia-slot-tooltip__name').textContent =
            isFilled ? d.materiaName : 'Slot vide';

        // Element
        const elemEl = panel.querySelector('.materia-slot-tooltip__element');
        if (isFilled) {
            const elemLabel = this._elementLabel(d.materiaElement);
            elemEl.textContent = elemLabel;
            elemEl.className = 'materia-slot-tooltip__element ' + this._elementTextColor(d.materiaElement);
            elemEl.style.display = '';
        } else {
            const slotElem = d.slotElement;
            if (slotElem && slotElem !== 'none') {
                elemEl.textContent = 'Élément requis : ' + d.slotElementLabel;
                elemEl.className = 'materia-slot-tooltip__element ' + this._elementTextColor(slotElem);
                elemEl.style.display = '';
            } else {
                elemEl.style.display = 'none';
            }
        }

        // Level + XP
        const levelEl = panel.querySelector('.materia-slot-tooltip__level-label');
        const xpEl = panel.querySelector('.materia-slot-tooltip__xp-label');
        const xpBar = panel.querySelector('.materia-slot-tooltip__xp-fill');

        if (isFilled) {
            const lvl = d.materiaLevel;
            levelEl.textContent = 'Niv. ' + lvl + '/5';
            if (parseInt(lvl) >= 5) {
                xpEl.textContent = 'MAX';
                xpBar.style.width = '100%';
            } else {
                xpEl.textContent = d.materiaXp + '/' + d.materiaXpNext + ' XP';
                xpBar.style.width = d.materiaXpPercent + '%';
            }
            levelEl.style.display = '';
            xpEl.style.display = '';
            panel.querySelector('.materia-slot-tooltip__xp-bar').style.display = '';
        } else {
            levelEl.style.display = 'none';
            xpEl.style.display = 'none';
            panel.querySelector('.materia-slot-tooltip__xp-bar').style.display = 'none';
        }

        // Spell
        const spellEl = panel.querySelector('.materia-slot-tooltip__spell');
        if (isFilled && d.materiaSpell) {
            spellEl.innerHTML = '<span class="text-gray-500">Sort :</span> ' + this._esc(d.materiaSpell);
            if (d.materiaSpellDesc) {
                spellEl.innerHTML += '<br><span class="text-[10px] text-gray-600">' + this._esc(d.materiaSpellDesc) + '</span>';
            }
            spellEl.style.display = '';
        } else {
            spellEl.style.display = 'none';
        }

        // Bonuses
        const bonusesEl = panel.querySelector('.materia-slot-tooltip__bonuses');
        bonusesEl.innerHTML = '';
        if (isFilled) {
            if (d.materiaMatch === '1') {
                bonusesEl.innerHTML += '<div class="materia-slot-tooltip__bonus materia-slot-tooltip__bonus--match">+25% XP — Bonus élémentaire</div>';
            }
            if (d.materiaSynergy === '1') {
                bonusesEl.innerHTML += '<div class="materia-slot-tooltip__bonus materia-slot-tooltip__bonus--synergy">+15% Dégâts — Synergie liée</div>';
            }
        }

        // Actions
        const actionsEl = panel.querySelector('.materia-slot-tooltip__actions');
        actionsEl.innerHTML = '';

        if (isFilled) {
            // Unset button
            const unsetForm = document.createElement('form');
            unsetForm.method = 'post';
            unsetForm.action = d.unsetUrl;
            unsetForm.setAttribute('data-turbo-frame', 'inventory-content');
            unsetForm.innerHTML = '<button type="submit" class="materia-slot-tooltip__btn materia-slot-tooltip__btn--unset">Retirer</button>';
            actionsEl.appendChild(unsetForm);

            // Replace link
            const replaceLink = document.createElement('a');
            replaceLink.href = d.replaceUrl;
            replaceLink.setAttribute('data-turbo-frame', 'inventory-content');
            replaceLink.className = 'materia-slot-tooltip__btn materia-slot-tooltip__btn--replace';
            replaceLink.textContent = 'Remplacer';
            actionsEl.appendChild(replaceLink);
        } else {
            const setLink = document.createElement('a');
            setLink.href = d.setUrl;
            setLink.setAttribute('data-turbo-frame', 'inventory-content');
            setLink.className = 'materia-slot-tooltip__btn materia-slot-tooltip__btn--set';
            setLink.textContent = 'Sertir';
            actionsEl.appendChild(setLink);
        }
    }

    // ---- Helpers ----

    _elementLabel(e) {
        const map = { fire: 'Feu', water: 'Eau', earth: 'Terre', air: 'Air', light: 'Lumière', dark: 'Ténèbres', metal: 'Métal', beast: 'Bête', none: '—' };
        return map[e] || e;
    }

    _elementTextColor(e) {
        const map = {
            fire: 'text-red-400', water: 'text-blue-400', earth: 'text-amber-400',
            air: 'text-cyan-400', light: 'text-yellow-300', dark: 'text-violet-400',
            metal: 'text-gray-300', beast: 'text-green-400',
        };
        return map[e] || 'text-gray-400';
    }

    _esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
