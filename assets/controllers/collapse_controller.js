import { Controller } from '@hotwired/stimulus';

/**
 * Sections repliables, avec memoire de l'etat.
 *
 * L'inventaire empilait toutes ses listes a plat : avec 31 outils, atteindre
 * une faucille demandait de derouler tout le reste. Chaque section devient un
 * bloc repliable, et son etat survit a la navigation (localStorage) : replier
 * une fois suffit.
 *
 * Usage :
 *   <div data-controller="collapse" data-collapse-key-value="tools" data-collapse-open-value="true">
 *     <button data-action="collapse#toggle" data-collapse-target="trigger">…</button>
 *     <div data-collapse-target="content">…</div>
 *   </div>
 *
 * `key` est optionnel : sans lui, l'etat n'est pas memorise (section dont
 * l'ouverture par defaut doit primer).
 */
export default class extends Controller {
    static targets = ['content', 'trigger', 'icon'];
    static values = {
        key: { type: String, default: '' },
        open: { type: Boolean, default: true },
    };

    static STORAGE_PREFIX = 'amethyste.collapse.';

    connect() {
        this._apply(this._initialState());
    }

    toggle(event) {
        event?.preventDefault();
        this._apply(!this._isOpen());
    }

    // ---- Etat ----

    _initialState() {
        const stored = this._read();
        return stored === null ? this.openValue : stored;
    }

    _isOpen() {
        return !this.element.classList.contains('collapse--closed');
    }

    _apply(open) {
        this.element.classList.toggle('collapse--closed', !open);

        this.contentTargets.forEach((el) => {
            // Une classe dediee, et pas seulement l'attribut `hidden` : une
            // utilitaire Tailwind (`grid`, `flex`) reposant sur `display` gagne
            // sur `[hidden]`, et la section restait visible une fois repliee.
            el.classList.toggle('collapse-content--hidden', !open);
            el.hidden = !open;
        });
        this.triggerTargets.forEach((el) => {
            el.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        this.iconTargets.forEach((el) => {
            el.classList.toggle('collapse-icon--open', open);
        });

        this._write(open);
    }

    // ---- Persistance ----

    _storageKey() {
        return this.keyValue === '' ? null : this.constructor.STORAGE_PREFIX + this.keyValue;
    }

    _read() {
        const key = this._storageKey();
        if (key === null) return null;
        try {
            const raw = window.localStorage.getItem(key);
            if (raw === null) return null;
            return raw === '1';
        } catch {
            // Navigation privee ou stockage refuse : l'etat par defaut suffit.
            return null;
        }
    }

    _write(open) {
        const key = this._storageKey();
        if (key === null) return;
        try {
            window.localStorage.setItem(key, open ? '1' : '0');
        } catch {
            // Sans stockage, la section reste simplement repliable a la main.
        }
    }
}
