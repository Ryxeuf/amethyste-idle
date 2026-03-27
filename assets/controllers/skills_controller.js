import { Controller } from '@hotwired/stimulus';

/**
 * Skills controller: domain sidebar navigation + modal management.
 *
 * Usage:
 *   <div data-controller="skills" data-skills-active-domain-value="42">
 *     <!-- Sidebar buttons -->
 *     <button data-skills-target="domainBtn"
 *             data-action="click->skills#selectDomain"
 *             data-domain-id="42">…</button>
 *
 *     <!-- Domain content panels (pre-rendered, toggled by JS) -->
 *     <div data-skills-target="domainPanel" data-domain-id="42">…</div>
 *
 *     <!-- Presets panel (collapsible) -->
 *     <div data-skills-target="presetsPanel" class="hidden">…</div>
 *     <button data-action="click->skills#togglePresets">Presets ▾</button>
 *
 *     <!-- Respec modal -->
 *     <div data-skills-target="respecModal" class="hidden">…</div>
 *     <button data-action="click->skills#openRespec">Redistribuer</button>
 *   </div>
 */
export default class extends Controller {
    static targets = ['domainBtn', 'domainPanel', 'presetsPanel', 'respecModal'];
    static values = { activeDomain: Number };

    connect() {
        this._showDomain(this.activeDomainValue);
    }

    selectDomain(event) {
        const id = parseInt(event.currentTarget.dataset.domainId, 10);
        if (id === this.activeDomainValue) return;
        this.activeDomainValue = id;
        this._showDomain(id);
    }

    togglePresets() {
        this.presetsPanelTargets.forEach(el => el.classList.toggle('hidden'));
    }

    openRespec() {
        this.respecModalTargets.forEach(el => el.classList.remove('hidden'));
    }

    closeRespec() {
        this.respecModalTargets.forEach(el => el.classList.add('hidden'));
    }

    closeRespecOnBackdrop(event) {
        if (event.target === event.currentTarget) {
            this.closeRespec();
        }
    }

    _showDomain(id) {
        this.domainPanelTargets.forEach(panel => {
            const match = parseInt(panel.dataset.domainId, 10) === id;
            panel.classList.toggle('hidden', !match);
        });

        this.domainBtnTargets.forEach(btn => {
            const match = parseInt(btn.dataset.domainId, 10) === id;
            btn.classList.toggle('skills-domain-btn--active', match);
            btn.setAttribute('aria-selected', match ? 'true' : 'false');
        });
    }
}
