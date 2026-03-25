import { Controller } from '@hotwired/stimulus'

const GEAR_TYPE = 'gear'
const MIN = 0
const MAX = 12

export default class extends Controller {
    static targets = ['input', 'typeSelect', 'panel', 'orbs', 'valueDisplay']

    connect() {
        this.syncFromInput()
        this.togglePanelVisibility()
    }

    typeChanged() {
        this.togglePanelVisibility()
    }

    togglePanelVisibility() {
        if (!this.hasTypeSelectTarget || !this.hasPanelTarget) {
            return
        }
        const isGear = this.typeSelectTarget.value === GEAR_TYPE
        this.panelTarget.classList.toggle('opacity-40', !isGear)
        this.panelTarget.classList.toggle('pointer-events-none', !isGear)
        this.panelTarget.setAttribute('aria-disabled', isGear ? 'false' : 'true')
    }

    syncFromInput() {
        if (!this.hasInputTarget) {
            return
        }
        const n = this.clamp(parseInt(this.inputTarget.value, 10) || 0)
        this.inputTarget.value = String(n)
        this.renderOrbs(n)
        if (this.hasValueDisplayTarget) {
            this.valueDisplayTarget.textContent = String(n)
        }
    }

    increment() {
        this.adjust(1)
    }

    decrement() {
        this.adjust(-1)
    }

    adjust(delta) {
        if (!this.hasInputTarget) {
            return
        }
        const current = parseInt(this.inputTarget.value, 10) || 0
        const next = this.clamp(current + delta)
        this.inputTarget.value = String(next)
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }))
        this.syncFromInput()
    }

    inputChanged() {
        this.syncFromInput()
    }

    clamp(n) {
        return Math.min(MAX, Math.max(MIN, n))
    }

    renderOrbs(count) {
        if (!this.hasOrbsTarget) {
            return
        }
        this.orbsTarget.replaceChildren()
        const frag = document.createDocumentFragment()
        for (let i = 0; i < MAX; i++) {
            const dot = document.createElement('span')
            dot.className =
                i < count
                    ? 'rounded-full w-3 h-3 sm:w-3.5 sm:h-3.5 ring-2 ring-cyan-400/70 bg-gradient-to-br from-cyan-400 to-violet-600 shadow-[0_0_10px_rgba(34,211,238,0.4)] transition-transform duration-200 hover:scale-110'
                    : 'rounded-full w-3 h-3 sm:w-3.5 sm:h-3.5 border border-dashed border-gray-600 bg-gray-800/40'
            dot.setAttribute('aria-hidden', 'true')
            frag.appendChild(dot)
        }
        this.orbsTarget.appendChild(frag)
    }
}
