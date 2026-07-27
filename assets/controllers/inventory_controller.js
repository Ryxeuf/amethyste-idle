import { Controller } from '@hotwired/stimulus';

/**
 * Inventaire : apercu au survol (bureau) et panneau de detail au clic.
 *
 * Un clic sur un objet ouvre sa fiche : panneau lateral sur grand ecran,
 * feuille remontante sur mobile. Le survol garde un apercu court, mais il ne
 * suffisait pas — les actions d'un objet n'y tenaient pas, et sur mobile il
 * n'existe pas.
 *
 * Usage :
 *   <div data-controller="inventory">
 *     <div data-inventory-target="item"
 *          data-item-name="Épée de feu"
 *          data-item-desc="Une épée enflammée"
 *          data-item-rarity="rare"
 *          data-item-protection="12"
 *          data-item-level="5"
 *          data-item-element="fire"
 *          data-item-slot="main_weapon"
 *          data-item-kind="gear"
 *          data-item-quantity="1"
 *          data-item-durability="80"
 *          data-item-durability-max="100"
 *          data-item-tier="2"
 *          data-item-value="120"
 *          data-item-bound="1"
 *          data-item-lock="locked_skill"
 *          data-item-effects="Brûle l'ennemi">
 *     </div>
 *     <!-- tooltip & panneau sont crees a la connexion -->
 *   </div>
 */
export default class extends Controller {
    static targets = ['item', 'tooltip', 'panel', 'panelBackdrop', 'tab', 'tabsNav', 'tabsWrapper'];
    static values = {
        labels: { type: Object, default: {} },
    };

    _label(key, fallback) {
        const value = this.labelsValue?.[key];
        return typeof value === 'string' && value.length > 0 ? value : fallback;
    }

    connect() {
        this._createTooltip();
        this._createPanel();
        this._isMobile = window.matchMedia('(hover: none)').matches;
        this._onResize = () => {
            this._isMobile = window.matchMedia('(hover: none)').matches;
            this._updateFades();
        };
        this._onKeydown = (event) => {
            if (event.key === 'Escape') this.closePanel();
        };
        window.addEventListener('resize', this._onResize);
        document.addEventListener('keydown', this._onKeydown);
        this._initScrollFades();
    }

    disconnect() {
        window.removeEventListener('resize', this._onResize);
        document.removeEventListener('keydown', this._onKeydown);
        document.body.style.overflow = '';
        this._tooltip?.remove();
        this._panel?.remove();
        this._backdrop?.remove();
    }

    // ---- Tab switching ----

    switchTab(event) {
        const tab = event.currentTarget;
        this.tabTargets.forEach(t => t.classList.remove('inv-tab--active'));
        tab.classList.add('inv-tab--active');
        // La fiche ouverte parle d'un objet de l'onglet qu'on quitte.
        this.closePanel();
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
            <div class="inv-tooltip-compare"></div>
            <div class="inv-tooltip-materia"></div>
            <div class="inv-tooltip-effects"></div>
            <div class="inv-tooltip-hint"></div>
        `;
        el.querySelector('.inv-tooltip-hint').textContent = this._label('open_details_hint', 'Cliquez pour les détails');
        this.element.appendChild(el);
        this._tooltip = el;
    }

    // ---- Panneau de detail (lateral sur bureau, feuille sur mobile) ----

    _createPanel() {
        const backdrop = document.createElement('div');
        backdrop.className = 'inv-panel-backdrop';
        backdrop.setAttribute('data-inventory-target', 'panelBackdrop');
        backdrop.addEventListener('click', () => this.closePanel());

        const panel = document.createElement('div');
        panel.className = 'inv-panel';
        panel.setAttribute('data-inventory-target', 'panel');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', this._label('details_title', 'Détails de l’objet'));
        panel.innerHTML = `
            <div class="inv-panel-handle"></div>
            <div class="inv-panel-header">
                <span class="inv-panel-name"></span>
                <button class="inv-panel-close" type="button" aria-label=""></button>
            </div>
            <div class="inv-panel-badges">
                <span class="inv-panel-rarity"></span>
                <span class="inv-panel-kind"></span>
                <span class="inv-panel-bound"></span>
            </div>
            <div class="inv-panel-slot"></div>
            <div class="inv-panel-desc"></div>
            <div class="inv-panel-stats"></div>
            <div class="inv-panel-durability"></div>
            <div class="inv-panel-compare"></div>
            <div class="inv-panel-materia"></div>
            <div class="inv-panel-effects"></div>
            <div class="inv-panel-lock"></div>
            <div class="inv-panel-actions"></div>
        `;
        const closeButton = panel.querySelector('.inv-panel-close');
        closeButton.textContent = '×';
        closeButton.setAttribute('aria-label', this._label('close', 'Fermer'));
        closeButton.addEventListener('click', () => this.closePanel());

        this.element.appendChild(backdrop);
        this.element.appendChild(panel);
        this._panel = panel;
        this._backdrop = backdrop;
    }

    // Called when an item target connects
    itemTargetConnected(el) {
        el.addEventListener('mouseenter', (e) => this._showTooltip(e, el));
        el.addEventListener('mouseleave', () => this._hideTooltip());
        el.addEventListener('mousemove', (e) => this._moveTooltip(e));
        // Clic : ouvre la fiche, sauf sur un element deja actionnable (slots
        // materia, lien de modification, bouton d'equipement) — le geste direct
        // doit rester direct.
        el.addEventListener('click', (e) => {
            if (e.target.closest('.materia-slots-bar')) return;
            if (e.target.closest('.materia-slots-track')) return;
            if (e.target.closest('a[href]')) return;
            if (e.target.closest('form')) return;
            if (e.target.closest('button')) return;
            e.preventDefault();
            e.stopPropagation();
            this._hideTooltip();
            this.openPanel(el);
        });
        // Focusable au clavier, donc activable au clavier : un element qui prend
        // le focus sans repondre a Entree est un piege.
        el.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            if (e.target !== el) return;
            e.preventDefault();
            this.openPanel(el);
        });
        el.style.cursor = 'pointer';
        if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
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
        const slotText = this._slotText(data);
        if (slotText) {
            slotEl.textContent = slotText;
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
        const stats = this._statList(data);
        if (stats.length) {
            statsEl.innerHTML = stats.map(s => '<span>' + this._escHtml(s) + '</span>').join('');
            statsEl.style.display = '';
        } else {
            statsEl.style.display = 'none';
        }

        this._fillCompareSection(t.querySelector('.inv-tooltip-compare'), data);
        this._fillMateriaSection(t.querySelector('.inv-tooltip-materia'), data);

        const fxEl = t.querySelector('.inv-tooltip-effects');
        if (data.effects) {
            fxEl.textContent = data.effects;
            fxEl.style.display = '';
        } else {
            fxEl.style.display = 'none';
        }
    }

    // ---- Panneau de detail ----

    openPanel(el) {
        const data = this._extractData(el);
        if (!data.name) return;

        const p = this._panel;
        p.querySelector('.inv-panel-name').textContent = data.name;

        const rarityEl = p.querySelector('.inv-panel-rarity');
        if (data.rarity) {
            rarityEl.textContent = this._rarityLabel(data.rarity);
            rarityEl.className = 'inv-panel-rarity inv-panel-rarity--' + data.rarity;
            rarityEl.style.display = '';
        } else {
            rarityEl.style.display = 'none';
        }

        const kindEl = p.querySelector('.inv-panel-kind');
        if (data.kind) {
            kindEl.textContent = this._kindLabel(data.kind);
            kindEl.style.display = '';
        } else {
            kindEl.style.display = 'none';
        }

        const boundEl = p.querySelector('.inv-panel-bound');
        if (data.bound === '1') {
            boundEl.textContent = this._label('bound', 'Lié au personnage');
            boundEl.style.display = '';
        } else {
            boundEl.style.display = 'none';
        }

        const slotEl = p.querySelector('.inv-panel-slot');
        const slotText = this._slotText(data);
        if (slotText) {
            slotEl.textContent = slotText;
            slotEl.style.display = '';
        } else {
            slotEl.style.display = 'none';
        }

        const descEl = p.querySelector('.inv-panel-desc');
        if (data.desc) {
            descEl.textContent = data.desc;
            descEl.style.display = '';
        } else {
            descEl.style.display = 'none';
        }

        const statsEl = p.querySelector('.inv-panel-stats');
        const stats = this._statList(data);
        if (stats.length) {
            statsEl.innerHTML = stats
                .map(s => '<span class="inv-panel-stat">' + this._escHtml(s) + '</span>')
                .join('');
            statsEl.style.display = '';
        } else {
            statsEl.style.display = 'none';
        }

        this._fillDurabilitySection(p.querySelector('.inv-panel-durability'), data);
        this._fillCompareSection(p.querySelector('.inv-panel-compare'), data);
        this._fillMateriaSection(p.querySelector('.inv-panel-materia'), data);

        const fxEl = p.querySelector('.inv-panel-effects');
        if (data.effects) {
            fxEl.textContent = data.effects;
            fxEl.style.display = '';
        } else {
            fxEl.style.display = 'none';
        }

        // Un objet verrouille doit dire pourquoi, ici comme dans la liste.
        const lockEl = p.querySelector('.inv-panel-lock');
        if (data.lock) {
            lockEl.textContent = this._label('lock.' + data.lock, data.lock);
            lockEl.style.display = '';
        } else {
            lockEl.style.display = 'none';
        }

        // Les actions sont celles de la ligne : les cloner evite de reconstruire
        // (et de desynchroniser) les regles qui decident ce qui est possible.
        const actionsEl = p.querySelector('.inv-panel-actions');
        actionsEl.innerHTML = '';
        el.querySelectorAll('form').forEach((form) => {
            const clone = form.cloneNode(true);
            const btn = clone.querySelector('button');
            if (btn) {
                btn.className = 'inv-panel-action-btn';
                const label = btn.getAttribute('title');
                if (label) btn.textContent = label;
            }
            // L'action recharge la liste : garder la fiche ouverte laisserait
            // decrire un objet dont l'etat vient de changer.
            clone.addEventListener('submit', () => this.closePanel());
            actionsEl.appendChild(clone);
        });
        el.querySelectorAll('a[href]').forEach((link) => {
            const clone = link.cloneNode(true);
            clone.className = 'inv-panel-action-btn inv-panel-action-btn--link';
            clone.textContent = link.getAttribute('title') || this._label('actions.details', 'Détails');
            actionsEl.appendChild(clone);
        });

        this._backdrop.classList.add('inv-panel-backdrop--visible');
        p.classList.add('inv-panel--visible');
        // Le verrou de defilement n'a de sens que pour la feuille mobile : sur
        // grand ecran, le panneau accompagne la page, il ne la remplace pas.
        if (this._isMobile) document.body.style.overflow = 'hidden';
    }

    closePanel() {
        if (!this._panel) return;
        this._panel.classList.remove('inv-panel--visible');
        this._backdrop.classList.remove('inv-panel-backdrop--visible');
        document.body.style.overflow = '';
    }

    // ---- Sections ----

    /**
     * Statistiques communes au survol et au panneau.
     */
    _statList(data) {
        const stats = [];
        if (data.protection && data.protection !== '0') stats.push('+' + data.protection + ' DEF');
        if (data.level && data.level !== '0') stats.push(this._label('level', 'Niveau ') + data.level);
        if (data.element && data.element !== 'none') stats.push(this._elementLabel(data.element));
        if (data.tier) stats.push(this._label('tier', 'Palier %tier%').replace('%tier%', data.tier));
        if (data.quantity && data.quantity !== '1') stats.push('x' + data.quantity);
        if (data.value && data.value !== '0') stats.push(data.value + ' ' + this._label('gils', 'Gils'));
        return stats;
    }

    _slotText(data) {
        if (data.toolType) return this._toolLabel(data.toolType);
        if (data.slot) return this._slotLabel(data.slot);
        return '';
    }

    _fillDurabilitySection(el, data) {
        const max = parseInt(data.durabilityMax) || 0;
        if (max <= 0 || data.durability === '') {
            el.style.display = 'none';
            return;
        }
        const current = parseInt(data.durability) || 0;
        const percent = Math.max(0, Math.min(100, Math.round((current / max) * 100)));
        const broken = current <= 0;
        el.innerHTML = `
            <div class="inv-panel-durability-head">
                <span>${this._escHtml(this._label('durability', 'Durabilité'))}</span>
                <span class="tabular-nums">${current}/${max}</span>
            </div>
            <div class="inv-panel-durability-bar">
                <div class="inv-panel-durability-fill${broken ? ' inv-panel-durability-fill--broken' : ''}" style="width: ${percent}%"></div>
            </div>
            ${broken ? '<div class="inv-panel-durability-broken">' + this._escHtml(this._label('broken', 'Outil cassé')) + '</div>' : ''}
        `;
        el.style.display = '';
    }

    // ---- Materia section ----

    _fillMateriaSection(el, data) {
        const total = parseInt(data.materiaTotal) || 0;
        if (total <= 0) {
            el.style.display = 'none';
            return;
        }
        const filled = parseInt(data.materiaFilled) || 0;
        const filledClass = filled > 0 ? 'text-purple-300' : 'text-gray-500';
        el.innerHTML = `
            <div class="flex items-center gap-1.5 text-[11px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-purple-400 shrink-0" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10" opacity=".3"/><circle cx="12" cy="12" r="5"/></svg>
                <span class="${filledClass}">${filled}/${total} Materia</span>
            </div>
        `;
        el.style.display = '';
    }

    // ---- Compare section ----

    _fillCompareSection(el, data) {
        // Only show comparison if there's an equipped item in the same slot
        if (!data.eqName && !data.slot) {
            el.style.display = 'none';
            return;
        }

        const lines = [];

        if (data.eqName) {
            // Compare against equipped item
            lines.push('<div class="inv-compare-title">' + this._escHtml(this._label('replaces', 'Remplace :')) + ' <span class="inv-compare-eq-name">' + this._escHtml(data.eqName) + '</span></div>');

            // Protection delta
            const newProt = parseInt(data.protection) || 0;
            const oldProt = parseInt(data.eqProtection) || 0;
            const deltaProt = newProt - oldProt;
            if (deltaProt !== 0) {
                lines.push(this._deltaLine('DEF', deltaProt));
            }

            // Materia slots delta
            const newMat = parseInt(data.materiaTotal) || 0;
            const oldMat = parseInt(data.eqMateriaTotal) || 0;
            const deltaMat = newMat - oldMat;
            if (deltaMat !== 0) {
                lines.push(this._deltaLine(this._label('materia_slots', 'Slots materia'), deltaMat));
            }

            // Element change
            const newElem = data.element && data.element !== 'none' ? data.element : '';
            const oldElem = data.eqElement && data.eqElement !== 'none' ? data.eqElement : '';
            if (newElem !== oldElem) {
                if (newElem && !oldElem) {
                    lines.push('<div class="inv-compare-line inv-compare-up">' + this._elementLabel(newElem) + '</div>');
                } else if (!newElem && oldElem) {
                    lines.push('<div class="inv-compare-line inv-compare-down">' + this._label('loses', 'Perd ') + this._elementLabel(oldElem) + '</div>');
                } else if (newElem && oldElem) {
                    lines.push('<div class="inv-compare-line inv-compare-neutral">' + this._elementLabel(oldElem) + ' → ' + this._elementLabel(newElem) + '</div>');
                }
            }

            if (lines.length === 1) {
                // Only the title, no actual deltas — items are equivalent
                lines.push('<div class="inv-compare-line inv-compare-neutral">' + this._label('identical_stats', 'Statistiques identiques') + '</div>');
            }
        } else {
            // Empty slot
            lines.push('<div class="inv-compare-title">' + this._label('empty_slot', 'Slot vide') + '</div>');
            const prot = parseInt(data.protection) || 0;
            if (prot > 0) {
                lines.push(this._deltaLine('DEF', prot));
            }
            const mat = parseInt(data.materiaTotal) || 0;
            if (mat > 0) {
                lines.push(this._deltaLine(this._label('materia_slots', 'Slots materia'), mat));
            }
        }

        el.innerHTML = lines.join('');
        el.style.display = '';
    }

    _deltaLine(label, delta) {
        const cls = delta > 0 ? 'inv-compare-up' : 'inv-compare-down';
        const sign = delta > 0 ? '+' : '';
        return '<div class="inv-compare-line ' + cls + '">' + sign + delta + ' ' + this._escHtml(label) + '</div>';
    }

    _escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
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
            kind: el.dataset.itemKind || '',
            toolType: el.dataset.itemToolType || '',
            tier: el.dataset.itemTier || '',
            quantity: el.dataset.itemQuantity || '',
            durability: el.dataset.itemDurability ?? '',
            durabilityMax: el.dataset.itemDurabilityMax || '',
            value: el.dataset.itemValue || '',
            bound: el.dataset.itemBound || '',
            lock: el.dataset.itemLock || '',
            materiaTotal: el.dataset.itemMateriaTotal || '0',
            materiaFilled: el.dataset.itemMateriaFilled || '0',
            eqName: el.dataset.itemEqName || '',
            eqProtection: el.dataset.itemEqProtection || '',
            eqMateriaTotal: el.dataset.itemEqMateriaTotal || '',
            eqElement: el.dataset.itemEqElement || '',
            eqRarity: el.dataset.itemEqRarity || '',
        };
    }

    _rarityLabel(r) {
        const fallbacks = { amethyst: 'Améthyste', legendary: 'Légendaire', epic: 'Épique', rare: 'Rare', uncommon: 'Peu commun', common: 'Commun' };
        return this._label('rarity.' + r, fallbacks[r] || r);
    }

    _slotLabel(s) {
        const fallbacks = {
            head: 'Tête', neck: 'Cou', chest: 'Torse', shoulder: 'Épaules',
            hand: 'Mains', main_weapon: 'Arme principale', side_weapon: 'Arme secondaire',
            belt: 'Ceinture', leg: 'Jambes', foot: 'Pieds',
            ring_1: 'Anneau 1', ring_2: 'Anneau 2',
        };
        return this._label('slot.' + s, fallbacks[s] || s);
    }

    _toolLabel(t) {
        return this._label('tool.' + t, t);
    }

    _kindLabel(k) {
        return this._label('kind.' + k, k);
    }

    _elementLabel(e) {
        const fallbacks = { fire: 'Feu', water: 'Eau', earth: 'Terre', air: 'Air', light: 'Lumière', dark: 'Ténèbres' };
        return this._label('element.' + e, fallbacks[e] || e);
    }
}
