import { Controller } from '@hotwired/stimulus'

const GEAR_TYPE = 'gear'
const MIN = 0
const MAX = 12

const ELEMENTS = [
    { value: 'none',  label: 'Aucun',    color: '#6b7280', glow: 'rgba(107,114,128,0.5)',  bg: '#374151' },
    { value: 'fire',  label: 'Feu',      color: '#ef4444', glow: 'rgba(239,68,68,0.6)',    bg: '#7f1d1d' },
    { value: 'water', label: 'Eau',      color: '#3b82f6', glow: 'rgba(59,130,246,0.6)',   bg: '#1e3a5f' },
    { value: 'earth', label: 'Terre',    color: '#f59e0b', glow: 'rgba(245,158,11,0.6)',   bg: '#78350f' },
    { value: 'air',   label: 'Air',      color: '#06b6d4', glow: 'rgba(6,182,212,0.6)',    bg: '#164e63' },
    { value: 'light', label: 'Lumiere',  color: '#fde047', glow: 'rgba(253,224,71,0.6)',   bg: '#713f12' },
    { value: 'dark',  label: 'Tenebres', color: '#a78bfa', glow: 'rgba(167,139,250,0.6)', bg: '#4c1d95' },
    { value: 'metal', label: 'Metal',    color: '#9ca3af', glow: 'rgba(156,163,175,0.5)', bg: '#4b5563' },
    { value: 'beast', label: 'Bete',     color: '#22c55e', glow: 'rgba(34,197,94,0.6)',   bg: '#14532d' },
]

function getElementDef(value) {
    return ELEMENTS.find(e => e.value === value) || ELEMENTS[0]
}

export default class extends Controller {
    static targets = ['input', 'typeSelect', 'panel', 'orbs', 'valueDisplay', 'configInput']

    connect() {
        this.slots = []
        this.linkMode = false
        this.linkFirst = null
        this.activeDropdown = null

        this._loadConfig()
        this.togglePanelVisibility()
        this._render()

        // Close dropdown on outside click
        this._onDocClick = (e) => {
            if (this.activeDropdown && !this.activeDropdown.contains(e.target)) {
                this._closeDropdown()
            }
        }
        document.addEventListener('click', this._onDocClick, true)
    }

    disconnect() {
        document.removeEventListener('click', this._onDocClick, true)
    }

    typeChanged() {
        this.togglePanelVisibility()
    }

    togglePanelVisibility() {
        if (!this.hasTypeSelectTarget || !this.hasPanelTarget) return
        const isGear = this.typeSelectTarget.value === GEAR_TYPE
        this.panelTarget.classList.toggle('opacity-40', !isGear)
        this.panelTarget.classList.toggle('pointer-events-none', !isGear)
        this.panelTarget.setAttribute('aria-disabled', isGear ? 'false' : 'true')
    }

    increment() { this._adjust(1) }
    decrement() { this._adjust(-1) }

    inputChanged() {
        if (!this.hasInputTarget) return
        const n = this._clamp(parseInt(this.inputTarget.value, 10) || 0)
        this.inputTarget.value = String(n)
        this._resizeSlots(n)
        this._render()
    }

    toggleLinkMode() {
        this.linkMode = !this.linkMode
        this.linkFirst = null
        this._render()
    }

    // --- Private ---

    _loadConfig() {
        if (this.hasConfigInputTarget && this.configInputTarget.value) {
            try {
                const parsed = JSON.parse(this.configInputTarget.value)
                if (Array.isArray(parsed) && parsed.length > 0) {
                    this.slots = parsed.map(s => ({
                        element: s.element || 'none',
                        linked: s.linked ?? null,
                    }))
                    // Sync materiaSlots input
                    if (this.hasInputTarget) {
                        this.inputTarget.value = String(this.slots.length)
                    }
                    return
                }
            } catch { /* ignore */ }
        }

        // Fallback: build from materiaSlots count
        const count = this.hasInputTarget ? (parseInt(this.inputTarget.value, 10) || 0) : 0
        this.slots = Array.from({ length: count }, () => ({ element: 'none', linked: null }))
    }

    _saveConfig() {
        if (this.hasConfigInputTarget) {
            this.configInputTarget.value = JSON.stringify(this.slots)
        }
        if (this.hasInputTarget) {
            this.inputTarget.value = String(this.slots.length)
        }
        if (this.hasValueDisplayTarget) {
            this.valueDisplayTarget.textContent = String(this.slots.length)
        }
    }

    _adjust(delta) {
        if (!this.hasInputTarget) return
        const current = this.slots.length
        const next = this._clamp(current + delta)
        this._resizeSlots(next)
        this._render()
    }

    _resizeSlots(count) {
        count = this._clamp(count)
        while (this.slots.length < count) {
            this.slots.push({ element: 'none', linked: null })
        }
        while (this.slots.length > count) {
            const removedIdx = this.slots.length - 1
            // Clean up any links pointing to this slot
            this.slots.forEach(s => {
                if (s.linked === removedIdx) s.linked = null
            })
            this.slots.pop()
        }
        this._saveConfig()
    }

    _clamp(n) {
        return Math.min(MAX, Math.max(MIN, n))
    }

    _render() {
        if (!this.hasOrbsTarget) return
        this.orbsTarget.replaceChildren()

        const container = document.createElement('div')
        container.className = 'flex flex-col gap-3'

        // Orbs row
        const orbsRow = document.createElement('div')
        orbsRow.className = 'flex flex-wrap gap-2.5 items-center'

        this.slots.forEach((slot, idx) => {
            const el = getElementDef(slot.element)
            const wrapper = document.createElement('div')
            wrapper.className = 'relative group'

            // Link indicator (before orb)
            if (slot.linked !== null && slot.linked < idx) {
                const linkBadge = document.createElement('span')
                linkBadge.className = 'absolute -left-3 top-1/2 -translate-y-1/2 text-cyan-400 text-xs font-bold select-none'
                linkBadge.textContent = '⟷'
                linkBadge.title = `Lie au slot ${slot.linked + 1}`
                wrapper.appendChild(linkBadge)
            }

            const orb = document.createElement('button')
            orb.type = 'button'
            orb.className = 'relative flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full border-2 transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-offset-gray-900'
            orb.title = `Slot ${idx + 1} — ${el.label}`

            if (slot.element === 'none') {
                orb.style.borderColor = '#4b5563'
                orb.style.borderStyle = 'dashed'
                orb.style.backgroundColor = 'rgba(31,41,55,0.6)'
                orb.classList.add('focus:ring-gray-500')
            } else {
                orb.style.borderColor = el.color
                orb.style.backgroundColor = el.bg
                orb.style.boxShadow = `0 0 12px ${el.glow}, inset 0 0 8px ${el.glow}`
                orb.classList.add('focus:ring-cyan-500')
            }

            // Link mode highlight
            if (this.linkMode) {
                orb.classList.add('ring-2', 'ring-cyan-400/50')
                if (this.linkFirst === idx) {
                    orb.classList.add('ring-4', 'ring-cyan-300', 'scale-110')
                }
            }

            // Inner dot for element color
            if (slot.element !== 'none') {
                const dot = document.createElement('span')
                dot.className = 'w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full'
                dot.style.backgroundColor = el.color
                dot.style.boxShadow = `0 0 6px ${el.glow}`
                orb.appendChild(dot)
            } else {
                const plus = document.createElement('span')
                plus.className = 'text-gray-500 text-sm font-bold'
                plus.textContent = '+'
                orb.appendChild(plus)
            }

            // Slot number badge
            const badge = document.createElement('span')
            badge.className = 'absolute -bottom-1 -right-1 w-4 h-4 flex items-center justify-center rounded-full bg-gray-800 border border-gray-600 text-[9px] font-bold text-gray-400'
            badge.textContent = String(idx + 1)
            orb.appendChild(badge)

            // Click handler
            orb.addEventListener('click', (e) => {
                e.stopPropagation()
                if (this.linkMode) {
                    this._handleLinkClick(idx)
                } else {
                    this._showDropdown(wrapper, idx)
                }
            })

            wrapper.appendChild(orb)
            orbsRow.appendChild(wrapper)
        })

        // Empty slot placeholders (up to MAX)
        for (let i = this.slots.length; i < MAX; i++) {
            const empty = document.createElement('span')
            empty.className = 'w-9 h-9 sm:w-10 sm:h-10 rounded-full border border-dashed border-gray-700/40 bg-gray-900/20'
            orbsRow.appendChild(empty)
        }

        container.appendChild(orbsRow)

        // Links summary
        const links = this.slots
            .map((s, i) => s.linked !== null ? { from: Math.min(i, s.linked) + 1, to: Math.max(i, s.linked) + 1 } : null)
            .filter(Boolean)
        const seen = new Set()
        const uniqueLinks = links.filter(l => {
            const key = `${l.from}-${l.to}`
            if (seen.has(key)) return false
            seen.add(key)
            return true
        })

        if (uniqueLinks.length > 0) {
            const linksRow = document.createElement('div')
            linksRow.className = 'flex flex-wrap gap-1.5'
            uniqueLinks.forEach(l => {
                const tag = document.createElement('span')
                tag.className = 'inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-cyan-950/60 text-cyan-300 border border-cyan-800/40'
                tag.innerHTML = `<span class="text-cyan-500">⟷</span> Slot ${l.from} — Slot ${l.to}`
                linksRow.appendChild(tag)
            })
            container.appendChild(linksRow)
        }

        // Link mode toggle button
        const toolbar = document.createElement('div')
        toolbar.className = 'flex items-center gap-3 mt-1'

        const linkBtn = document.createElement('button')
        linkBtn.type = 'button'
        linkBtn.className = this.linkMode
            ? 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-cyan-600 text-white border border-cyan-500 transition-colors'
            : 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-800 text-gray-400 border border-gray-600 hover:border-cyan-600/50 hover:text-cyan-300 transition-colors'
        linkBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>'
        linkBtn.innerHTML += this.linkMode ? ' Mode liaison actif' : ' Lier des slots'
        linkBtn.addEventListener('click', () => this.toggleLinkMode())
        toolbar.appendChild(linkBtn)

        if (this.linkMode) {
            const hint = document.createElement('span')
            hint.className = 'text-[10px] text-cyan-400/70'
            hint.textContent = 'Cliquez sur 2 slots pour les lier (recliquez pour annuler une liaison)'
            toolbar.appendChild(hint)
        }

        container.appendChild(toolbar)
        this.orbsTarget.appendChild(container)
    }

    _showDropdown(wrapper, idx) {
        this._closeDropdown()

        const dropdown = document.createElement('div')
        dropdown.className = 'absolute z-50 mt-1 left-1/2 -translate-x-1/2 bg-gray-800 border border-gray-600 rounded-lg shadow-xl py-1 min-w-[140px] animate-in fade-in duration-150'
        dropdown.style.top = '100%'

        ELEMENTS.forEach(el => {
            const opt = document.createElement('button')
            opt.type = 'button'
            opt.className = 'flex items-center gap-2 w-full px-3 py-1.5 text-xs text-left hover:bg-gray-700 transition-colors'
            if (this.slots[idx].element === el.value) {
                opt.classList.add('bg-gray-700/50')
            }

            const dot = document.createElement('span')
            dot.className = 'w-3 h-3 rounded-full shrink-0 border'
            if (el.value === 'none') {
                dot.style.borderColor = '#6b7280'
                dot.style.borderStyle = 'dashed'
                dot.style.backgroundColor = 'transparent'
            } else {
                dot.style.backgroundColor = el.color
                dot.style.borderColor = el.color
                dot.style.boxShadow = `0 0 6px ${el.glow}`
            }

            const label = document.createElement('span')
            label.className = 'text-gray-200'
            label.textContent = el.label

            opt.appendChild(dot)
            opt.appendChild(label)

            opt.addEventListener('click', (e) => {
                e.stopPropagation()
                this.slots[idx].element = el.value
                this._saveConfig()
                this._closeDropdown()
                this._render()
            })

            dropdown.appendChild(opt)
        })

        // Remove link option if linked
        if (this.slots[idx].linked !== null) {
            const sep = document.createElement('div')
            sep.className = 'border-t border-gray-600 my-1'
            dropdown.appendChild(sep)

            const unlink = document.createElement('button')
            unlink.type = 'button'
            unlink.className = 'flex items-center gap-2 w-full px-3 py-1.5 text-xs text-left text-red-400 hover:bg-gray-700 transition-colors'
            unlink.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg> Retirer la liaison'
            unlink.addEventListener('click', (e) => {
                e.stopPropagation()
                const linkedIdx = this.slots[idx].linked
                if (linkedIdx !== null && this.slots[linkedIdx]) {
                    this.slots[linkedIdx].linked = null
                }
                this.slots[idx].linked = null
                this._saveConfig()
                this._closeDropdown()
                this._render()
            })
            dropdown.appendChild(unlink)
        }

        wrapper.style.position = 'relative'
        wrapper.appendChild(dropdown)
        this.activeDropdown = dropdown
    }

    _closeDropdown() {
        if (this.activeDropdown) {
            this.activeDropdown.remove()
            this.activeDropdown = null
        }
    }

    _handleLinkClick(idx) {
        if (this.linkFirst === null) {
            this.linkFirst = idx
            this._render()
            return
        }

        if (this.linkFirst === idx) {
            // Deselect
            this.linkFirst = null
            this._render()
            return
        }

        const a = this.linkFirst
        const b = idx

        // Check if already linked — toggle off
        if (this.slots[a].linked === b) {
            this.slots[a].linked = null
            this.slots[b].linked = null
        } else {
            // Remove any previous links on both
            this.slots.forEach((s, i) => {
                if (s.linked === a || s.linked === b) s.linked = null
            })
            if (this.slots[a].linked !== null) {
                const prev = this.slots[a].linked
                if (this.slots[prev]) this.slots[prev].linked = null
            }
            if (this.slots[b].linked !== null) {
                const prev = this.slots[b].linked
                if (this.slots[prev]) this.slots[prev].linked = null
            }
            this.slots[a].linked = b
            this.slots[b].linked = a
        }

        this.linkFirst = null
        this.linkMode = false
        this._saveConfig()
        this._render()
    }
}
