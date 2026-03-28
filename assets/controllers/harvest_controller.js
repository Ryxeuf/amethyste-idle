import { Controller } from '@hotwired/stimulus';

/**
 * Harvest controller for gathering resources from map spots.
 * Shows a contextual panel when the player is near a harvest spot.
 *
 * Listens for 'map_pixi:harvestSpot' Stimulus events.
 */
export default class extends Controller {
    static targets = ['panel', 'spotName', 'toolType', 'message', 'harvestBtn', 'resultList', 'cooldownBar'];

    connect() {
        this._currentSpot = null;
        this._harvesting = false;
        this._cooldownTimer = null;
    }

    disconnect() {
        this._clearCooldown();
    }

    /** Called from map controller via Stimulus dispatch */
    open(event) {
        const spot = event.detail;
        if (!spot || this._harvesting) return;

        this._currentSpot = spot;

        this.spotNameTarget.textContent = spot.name || 'Spot de récolte';
        this.toolTypeTarget.textContent = this._toolLabel(spot.toolType);
        this.messageTarget.textContent = '';
        this.resultListTarget.innerHTML = '';

        // Check if player has the skill to harvest this spot
        if (spot.canHarvest === false) {
            this.harvestBtnTarget.disabled = true;
            this.harvestBtnTarget.textContent = 'Compétence requise';
            this.messageTarget.textContent = 'Vous n\'avez pas la compétence pour récolter ce spot.';
            this.messageTarget.className = 'text-yellow-400 text-sm mt-2';
        } else if (!spot.available) {
            this.harvestBtnTarget.disabled = true;
            this.harvestBtnTarget.textContent = `Respawn ${spot.remainingSeconds || '...'}s`;
        } else {
            this.harvestBtnTarget.disabled = false;
            this.harvestBtnTarget.textContent = this._harvestLabel(spot.toolType);
        }

        this.panelTarget.classList.remove('hidden');
        this.panelTarget.style.transform = 'translateY(10px)';
        this.panelTarget.style.opacity = '0';
        this.panelTarget.style.transition = 'transform 0.2s ease-out, opacity 0.2s ease-out';
        requestAnimationFrame(() => {
            this.panelTarget.style.transform = 'translateY(0)';
            this.panelTarget.style.opacity = '1';
        });
    }

    close() {
        this.panelTarget.style.transform = 'translateY(10px)';
        this.panelTarget.style.opacity = '0';
        setTimeout(() => {
            this.panelTarget.classList.add('hidden');
            this._currentSpot = null;
        }, 200);
    }

    async harvest() {
        if (!this._currentSpot || this._harvesting) return;
        if (this._currentSpot.canHarvest === false) return;

        this._harvesting = true;
        this.harvestBtnTarget.disabled = true;
        this.harvestBtnTarget.textContent = 'Récolte en cours...';
        this.messageTarget.textContent = '';
        this.resultListTarget.innerHTML = '';

        const spotId = this._currentSpot.id;
        const isFishing = this._currentSpot.toolType === 'fishing_rod';

        try {
            let result;
            if (isFishing) {
                // Dispatch fishing mini-game event instead
                this.dispatch('startFishing', { detail: this._currentSpot });
                this._harvesting = false;
                this.harvestBtnTarget.disabled = false;
                this.harvestBtnTarget.textContent = this._harvestLabel(this._currentSpot.toolType);
                this.close();
                return;
            }

            const resp = await fetch(`/api/gathering/harvest/${spotId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            result = await resp.json();

            if (result.error) {
                this.messageTarget.textContent = result.error;
                this.messageTarget.className = 'text-red-400 text-sm mt-2';
            } else if (result.success) {
                if (window.Sound) window.Sound.play('harvest');
                this.messageTarget.textContent = 'Récolte réussie !';
                this.messageTarget.className = 'text-green-400 text-sm mt-2';

                // Notify map controller to spawn green particles
                this.dispatch('harvestSuccess', { detail: { items: result.items || [] } });

                // Show harvested items
                for (const item of (result.items || [])) {
                    const li = document.createElement('div');
                    li.className = 'text-yellow-300 text-xs mt-1';
                    li.textContent = `+ ${item.name}`;
                    this.resultListTarget.appendChild(li);
                }

                if (result.toolBroken) {
                    const warn = document.createElement('div');
                    warn.className = 'text-red-400 text-xs mt-1 font-bold';
                    warn.textContent = 'Votre outil est brisé !';
                    this.resultListTarget.appendChild(warn);
                }

                // Start cooldown display
                if (result.respawnDelay) {
                    this._startCooldown(result.respawnDelay);
                }
            } else {
                this.messageTarget.textContent = 'Aucune ressource obtenue.';
                this.messageTarget.className = 'text-gray-400 text-sm mt-2';
            }
        } catch (err) {
            console.error('[harvest] Error:', err);
            this.messageTarget.textContent = 'Erreur de connexion.';
            this.messageTarget.className = 'text-red-400 text-sm mt-2';
        } finally {
            this._harvesting = false;
            this.harvestBtnTarget.disabled = false;
            this.harvestBtnTarget.textContent = this._harvestLabel(this._currentSpot?.toolType);
        }
    }

    _startCooldown(seconds) {
        this.harvestBtnTarget.disabled = true;
        let remaining = seconds;

        const update = () => {
            if (remaining <= 0) {
                this.harvestBtnTarget.disabled = false;
                this.harvestBtnTarget.textContent = this._harvestLabel(this._currentSpot?.toolType);
                if (this.hasCooldownBarTarget) {
                    this.cooldownBarTarget.style.width = '100%';
                }
                return;
            }
            this.harvestBtnTarget.textContent = `Respawn ${remaining}s`;
            if (this.hasCooldownBarTarget) {
                const pct = ((seconds - remaining) / seconds) * 100;
                this.cooldownBarTarget.style.width = `${pct}%`;
            }
            remaining--;
            this._cooldownTimer = setTimeout(update, 1000);
        };
        update();
    }

    _clearCooldown() {
        if (this._cooldownTimer) {
            clearTimeout(this._cooldownTimer);
            this._cooldownTimer = null;
        }
    }

    _toolLabel(toolType) {
        const labels = {
            pickaxe: 'Pioche requise',
            sickle: 'Faucille requise',
            fishing_rod: 'Canne à pêche requise',
            skinning_knife: 'Couteau de dépeçage requis',
        };
        return labels[toolType] || 'Outil requis';
    }

    _harvestLabel(toolType) {
        const labels = {
            pickaxe: 'Miner',
            sickle: 'Récolter',
            fishing_rod: 'Pêcher',
            skinning_knife: 'Dépecer',
        };
        return labels[toolType] || 'Récolter';
    }
}
