import { Controller } from '@hotwired/stimulus';

/**
 * Harvest controller for gathering resources from map spots.
 * Shows a rich contextual panel when the player clicks a harvest spot.
 *
 * Listens for 'map_pixi:harvestSpot' Stimulus events.
 */
export default class extends Controller {
    static targets = [
        'panel', 'spotName', 'spotIcon', 'domainBadge',
        'loading', 'content',
        'toolInfo', 'toolIcon', 'toolName', 'toolDurabilityBar', 'toolDurabilityText',
        'noToolWarning', 'toolType',
        'itemsSection', 'itemList',
        'harvestBtn', 'harvestBtnText', 'progressBar',
        'cooldownSection', 'cooldownBar', 'cooldownText',
        'resultSection', 'message', 'resultList',
        'xpGain', 'xpGainText',
    ];
    static values = {
        labels: { type: Object, default: {} },
    };

    connect() {
        this._currentSpot = null;
        this._harvesting = false;
        this._cooldownTimer = null;
        this._boundKeyHandler = this._handleKeydown.bind(this);
        console.warn('[harvest] Controller connected ✓');
    }

    _label(key, fallback) {
        const value = this.labelsValue?.[key];
        return typeof value === 'string' && value.length > 0 ? value : fallback;
    }

    disconnect() {
        this._clearCooldown();
        document.removeEventListener('keydown', this._boundKeyHandler);
    }

    /** Called from map controller via Stimulus dispatch */
    async open(event) {
        const spot = event.detail;
        console.warn('[harvest] open() called — spot:', spot, 'harvesting:', this._harvesting);
        if (!spot || this._harvesting) return;

        this._currentSpot = spot;
        this._resetPanel();

        // Show panel with loading state
        this.spotNameTarget.textContent = spot.name || this._label('spotFallback', 'Spot de récolte');
        this.spotIconTarget.textContent = this._spotEmoji(spot.toolType);
        this.panelTarget.classList.remove('hidden');
        this.loadingTarget.classList.remove('hidden');
        this.contentTarget.classList.add('hidden');

        // Animate panel in
        this.panelTarget.style.transform = 'translateY(10px) scale(0.98)';
        this.panelTarget.style.opacity = '0';
        this.panelTarget.style.transition = 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease-out';
        requestAnimationFrame(() => {
            this.panelTarget.style.transform = 'translateY(0) scale(1)';
            this.panelTarget.style.opacity = '1';
        });

        // Enable keyboard shortcuts
        document.addEventListener('keydown', this._boundKeyHandler);

        // Check quick fail states before API call
        if (spot.canHarvest === false) {
            this._showNoSkill();
            return;
        }

        // Fetch spot details from API
        try {
            const resp = await fetch(`/api/gathering/spot/${spot.id}`);
            const data = await resp.json();
            console.debug('[harvest] spotInfo response:', data);

            if (!resp.ok) {
                this._showError(data.error || this._label('loadError', 'Impossible de charger les détails.'));
                return;
            }

            this._populatePanel(data, spot);
        } catch {
            this._showError(this._label('connectionError', 'Erreur de connexion.'));
        }
    }

    /** Close panel when the player starts moving */
    onPlayerMove() {
        if (this._currentSpot && !this._harvesting) {
            this.close();
        }
    }

    close() {
        document.removeEventListener('keydown', this._boundKeyHandler);
        this.panelTarget.style.transform = 'translateY(8px) scale(0.98)';
        this.panelTarget.style.opacity = '0';
        setTimeout(() => {
            this.panelTarget.classList.add('hidden');
            this._currentSpot = null;
            this._clearCooldown();
        }, 200);
    }

    async harvest() {
        if (!this._currentSpot || this._harvesting) return;
        if (this._currentSpot.canHarvest === false) return;
        if (this.harvestBtnTarget.disabled) return;

        const spotId = this._currentSpot.id;
        const isFishing = this._currentSpot.toolType === 'fishing_rod';

        if (isFishing) {
            this.dispatch('startFishing', { detail: this._currentSpot });
            this.close();
            return;
        }

        this._harvesting = true;
        this.harvestBtnTarget.disabled = true;
        this.harvestBtnTextTarget.textContent = this._label('harvesting', 'Récolte en cours…');

        // Clear previous results
        this._hideResults();

        // Animate progress bar
        this.progressBarTarget.style.transition = 'none';
        this.progressBarTarget.style.transform = 'scaleX(0)';
        requestAnimationFrame(() => {
            this.progressBarTarget.style.transition = 'transform 1.5s ease-in-out';
            this.progressBarTarget.style.transform = 'scaleX(1)';
        });

        try {
            const resp = await fetch(`/api/gathering/harvest/${spotId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            const result = await resp.json();

            // Reset progress bar
            setTimeout(() => {
                this.progressBarTarget.style.transition = 'transform 0.3s ease-out';
                this.progressBarTarget.style.transform = 'scaleX(0)';
            }, 300);

            if (!resp.ok) {
                console.debug('[harvest] harvest POST failed:', result);
                const errorText = result.error || this._label('cannotHarvest', 'Impossible de récolter ici.');
                this._showResultMessage(errorText, 'error');
                // Show toast for harvest error
                if (window.Toast) {
                    window.Toast.show('error', errorText, 6000);
                }
            } else if (result.success) {
                this._showHarvestResults(result);
                this.dispatch('harvestSuccess', { detail: { items: result.items || [], spotId } });
            } else {
                this._showResultMessage(this._label('emptyResult', 'Aucune ressource obtenue.'), 'empty');
            }
        } catch {
            this._showResultMessage(this._label('connectionError', 'Erreur de connexion.'), 'error');
        } finally {
            this._harvesting = false;
            this.harvestBtnTarget.disabled = false;
            this.harvestBtnTextTarget.textContent = this._harvestLabel(this._currentSpot?.toolType);
        }
    }

    // --- Private methods ---

    _resetPanel() {
        this._clearCooldown();
        this._hideResults();
        this.loadingTarget.classList.add('hidden');
        this.contentTarget.classList.remove('hidden');
        this.toolInfoTarget.classList.add('hidden');
        this.noToolWarningTarget.classList.add('hidden');
        this.itemsSectionTarget.classList.add('hidden');
        this.cooldownSectionTarget.classList.add('hidden');
        this.domainBadgeTarget.classList.add('hidden');
        this.itemListTarget.innerHTML = '';
        this.progressBarTarget.style.transform = 'scaleX(0)';
        this.harvestBtnTarget.disabled = false;
        this.harvestBtnTextTarget.textContent = this._label('harvestDefault', 'Récolter');
    }

    _populatePanel(data, spot) {
        this.loadingTarget.classList.add('hidden');
        this.contentTarget.classList.remove('hidden');
        console.debug('[harvest] _populatePanel — tool:', data.tool, 'toolType:', data.toolType, 'toolError:', data.toolError, 'available:', data.available);

        // Domain badge
        if (data.domain) {
            this.domainBadgeTarget.textContent = data.domain.name;
            this.domainBadgeTarget.classList.remove('hidden');
        }

        // Tool info
        if (data.tool) {
            this._showToolInfo(data.tool);
            // Show warning if tool has an issue (skill or durability)
            if (data.toolError) {
                this.noToolWarningTarget.classList.remove('hidden');
                this.toolTypeTarget.textContent = data.toolError;
                console.debug('[harvest] Tool found but error:', data.toolError);
                if (window.Toast) {
                    window.Toast.show('warning', data.toolError, 6000);
                }
            }
        } else if (data.toolType) {
            this.noToolWarningTarget.classList.remove('hidden');
            // Use specific error from API if available, otherwise generic label
            this.toolTypeTarget.textContent = data.toolError || this._toolLabel(data.toolType);
            console.debug('[harvest] No tool found — showing warning:', data.toolError || this._toolLabel(data.toolType));
            // Show toast notification for tool issue
            if (window.Toast) {
                window.Toast.show('warning', data.toolError || this._toolLabel(data.toolType), 6000);
            }
        }

        // Possible items
        if (data.possibleItems && data.possibleItems.length > 0) {
            this._showPossibleItems(data.possibleItems);
        }

        // Button label
        this.harvestBtnTextTarget.textContent = this._harvestLabel(spot.toolType);

        // Availability
        if (!data.available) {
            this.harvestBtnTarget.disabled = true;
        } else if (!data.tool && data.toolType) {
            this.harvestBtnTarget.disabled = true;
        } else if (data.toolError) {
            // Tool found but skill or durability issue
            this.harvestBtnTarget.disabled = true;
        }
    }

    _showToolInfo(tool) {
        this.toolInfoTarget.classList.remove('hidden');
        this.toolNameTarget.textContent = tool.name;
        this.toolIconTarget.textContent = this._toolEmoji(this._currentSpot?.toolType);

        const current = tool.currentDurability ?? 0;
        const max = tool.maxDurability ?? 1;
        const pct = Math.max(0, Math.min(100, (current / max) * 100));

        this.toolDurabilityTextTarget.textContent = `${current}/${max}`;
        this.toolDurabilityBarTarget.style.width = `${pct}%`;
        this.toolDurabilityBarTarget.className = `h-full rounded-full transition-all duration-500 ${this._durabilityColor(pct)}`;
    }

    _showPossibleItems(items) {
        this.itemsSectionTarget.classList.remove('hidden');
        this.itemListTarget.innerHTML = '';

        for (const item of items) {
            const el = document.createElement('div');
            el.className = `flex items-center gap-1.5 text-xs px-2 py-1.5 rounded-lg bg-gray-800/60 border border-gray-700/40 ${this._rarityBorderClass(item.rarity)}`;

            const icon = document.createElement('span');
            icon.className = 'text-sm shrink-0';
            icon.textContent = this._itemEmoji(item.slug);

            const nameSpan = document.createElement('span');
            nameSpan.className = `truncate ${this._rarityTextClass(item.rarity)}`;
            nameSpan.textContent = item.name;

            const qtySpan = document.createElement('span');
            qtySpan.className = 'text-gray-500 text-[10px] ml-auto shrink-0 tabular-nums';
            qtySpan.textContent = item.min === item.max ? `×${item.min}` : `${item.min}-${item.max}`;

            el.appendChild(icon);
            el.appendChild(nameSpan);
            el.appendChild(qtySpan);
            this.itemListTarget.appendChild(el);
        }
    }

    _showHarvestResults(result) {
        this.resultSectionTarget.classList.remove('hidden');

        // Message
        this._showResultMessage(this._label('harvestSuccess', 'Récolte réussie !'), 'success');

        // Items with staggered animation
        this.resultListTarget.innerHTML = '';
        (result.items || []).forEach((item, i) => {
            const el = document.createElement('div');
            el.className = `flex items-center gap-2 text-xs px-2 py-1 rounded-lg ${this._rarityBgClass(item.rarity)} opacity-0`;
            el.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            el.style.transform = 'translateX(-8px)';

            const icon = document.createElement('span');
            icon.textContent = this._itemEmoji(item.slug);

            const name = document.createElement('span');
            name.className = this._rarityTextClass(item.rarity);
            name.textContent = `+ ${item.name}`;

            el.appendChild(icon);
            el.appendChild(name);
            this.resultListTarget.appendChild(el);

            // Staggered fade-in
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateX(0)';
            }, 100 + i * 120);
        });

        // Tool broken warning
        if (result.toolBroken) {
            const warn = document.createElement('div');
            warn.className = 'flex items-center gap-1 text-red-400 text-xs font-bold mt-1 animate-pulse';
            const brokenLabel = this._label('toolBroken', 'Votre outil est brisé !');
            warn.innerHTML = `<span>🔨</span><span>${brokenLabel}</span>`;
            this.resultListTarget.appendChild(warn);
        }

        // Update tool durability bar
        if (result.toolDurability !== null && result.toolDurability !== undefined && this.hasToolInfoTarget) {
            this._animateToolDurability(result.toolDurability, result.toolMaxDurability || 1);
        }

        // XP gained
        if (result.xpGained > 0 && result.domainSlug) {
            this.xpGainTarget.classList.remove('hidden');
            this.xpGainTextTarget.textContent = `+${result.xpGained} XP`;
            // Pop animation
            this.xpGainTarget.style.transform = 'scale(0.5)';
            this.xpGainTarget.style.opacity = '0';
            this.xpGainTarget.style.transition = 'transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease-out';
            setTimeout(() => {
                this.xpGainTarget.style.transform = 'scale(1)';
                this.xpGainTarget.style.opacity = '1';
            }, (result.items?.length || 0) * 120 + 200);
        }

        // Fermer le panel après un délai pour que le joueur voie le résultat
        setTimeout(() => {
            this.close();
        }, 2500);
    }

    _showResultMessage(text, type) {
        this.resultSectionTarget.classList.remove('hidden');
        this.messageTarget.textContent = text;
        const classes = {
            success: 'text-emerald-400',
            error: 'text-red-400',
            empty: 'text-gray-400',
        };
        this.messageTarget.className = `text-sm font-medium mb-1.5 ${classes[type] || 'text-gray-400'}`;
    }

    _hideResults() {
        if (this.hasResultSectionTarget) this.resultSectionTarget.classList.add('hidden');
        if (this.hasResultListTarget) this.resultListTarget.innerHTML = '';
        if (this.hasMessageTarget) this.messageTarget.textContent = '';
        if (this.hasXpGainTarget) this.xpGainTarget.classList.add('hidden');
    }

    _showNoSkill() {
        this.loadingTarget.classList.add('hidden');
        this.contentTarget.classList.remove('hidden');
        this.harvestBtnTarget.disabled = true;
        this.harvestBtnTextTarget.textContent = this._label('skillRequired', 'Compétence requise');
        this._showResultMessage(this._label('skillMissing', 'Vous n\'avez pas la compétence pour récolter ce spot.'), 'error');
        console.debug('[harvest] canHarvest=false — skill missing for this spot');
        if (window.Toast) {
            window.Toast.show('warning', this._label('skillInsufficient', 'Compétence insuffisante pour récolter ce spot.'), 6000);
        }
    }

    _showError(text) {
        this.loadingTarget.classList.add('hidden');
        this.contentTarget.classList.remove('hidden');
        this._showResultMessage(text, 'error');
    }

    _animateToolDurability(current, max) {
        const pct = Math.max(0, Math.min(100, (current / max) * 100));
        this.toolDurabilityTextTarget.textContent = `${current}/${max}`;
        this.toolDurabilityBarTarget.style.width = `${pct}%`;
        this.toolDurabilityBarTarget.className = `h-full rounded-full transition-all duration-500 ${this._durabilityColor(pct)}`;
    }

    _startCooldown(seconds, total) {
        this.cooldownSectionTarget.classList.remove('hidden');
        this.harvestBtnTarget.disabled = true;
        let remaining = seconds;

        const update = () => {
            if (remaining <= 0) {
                this.harvestBtnTarget.disabled = false;
                this.harvestBtnTextTarget.textContent = this._harvestLabel(this._currentSpot?.toolType);
                this.cooldownBarTarget.style.width = '100%';
                this.cooldownTextTarget.textContent = this._label('ready', 'Prêt !');
                this.cooldownSectionTarget.classList.add('hidden');
                this._hideResults();
                return;
            }
            const pct = ((total - remaining) / total) * 100;
            this.cooldownBarTarget.style.width = `${pct}%`;
            this.cooldownTextTarget.textContent = `${remaining}s`;
            this.harvestBtnTextTarget.textContent = `${this._label('respawn', 'Respawn')} ${remaining}s`;
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

    _handleKeydown(e) {
        if (e.key === 'Escape') {
            this.close();
        } else if (e.key === ' ' || e.key === 'Enter') {
            if (!this.harvestBtnTarget.disabled && !this._harvesting) {
                e.preventDefault();
                this.harvest();
            }
        }
    }

    // --- Emoji / label helpers ---

    _spotEmoji(toolType) {
        return { pickaxe: '⛏️', sickle: '🌿', fishing_rod: '🎣', skinning_knife: '🔪' }[toolType] || '💎';
    }

    _toolEmoji(toolType) {
        return { pickaxe: '⛏️', sickle: '🌾', fishing_rod: '🎣', skinning_knife: '🗡️' }[toolType] || '🔧';
    }

    _toolLabel(toolType) {
        const map = {
            pickaxe: this._label('toolRequired.pickaxe', 'Pioche requise'),
            sickle: this._label('toolRequired.sickle', 'Faucille requise'),
            fishing_rod: this._label('toolRequired.fishing_rod', 'Canne à pêche requise'),
            skinning_knife: this._label('toolRequired.skinning_knife', 'Couteau de dépeçage requis'),
        };
        return map[toolType] || this._label('toolRequired.default', 'Outil requis');
    }

    _harvestLabel(toolType) {
        const map = {
            pickaxe: this._label('action.pickaxe', 'Miner'),
            sickle: this._label('action.sickle', 'Récolter'),
            fishing_rod: this._label('action.fishing_rod', 'Pêcher'),
            skinning_knife: this._label('action.skinning_knife', 'Dépecer'),
        };
        return map[toolType] || this._label('action.default', 'Récolter');
    }

    _itemEmoji(slug) {
        if (!slug) return '📦';
        if (slug.startsWith('ore-') || slug.startsWith('mineral-')) return '💎';
        if (slug.startsWith('herb-') || slug.startsWith('plant-') || slug.startsWith('flower-')) return '🌿';
        if (slug.startsWith('fish-')) return '🐟';
        if (slug.startsWith('leather-') || slug.startsWith('hide-') || slug.startsWith('skin-')) return '🧶';
        if (slug.startsWith('wood-') || slug.startsWith('log-')) return '🪵';
        if (slug.startsWith('gem-')) return '💠';
        return '📦';
    }

    _durabilityColor(pct) {
        if (pct > 60) return 'bg-emerald-500';
        if (pct > 30) return 'bg-yellow-500';
        return 'bg-red-500';
    }

    _rarityTextClass(rarity) {
        return { common: 'text-gray-300', uncommon: 'text-green-400', rare: 'text-blue-400', epic: 'text-purple-400', legendary: 'text-amber-400' }[rarity] || 'text-gray-300';
    }

    _rarityBorderClass(rarity) {
        return { uncommon: 'border-green-500/30', rare: 'border-blue-500/30', epic: 'border-purple-500/30', legendary: 'border-amber-500/30' }[rarity] || '';
    }

    _rarityBgClass(rarity) {
        return { common: 'bg-gray-800/40', uncommon: 'bg-green-900/20', rare: 'bg-blue-900/20', epic: 'bg-purple-900/20', legendary: 'bg-amber-900/20' }[rarity] || 'bg-gray-800/40';
    }
}
