import { Controller } from '@hotwired/stimulus'

/**
 * Highlights relevant navigation links based on the current tutorial step.
 *
 * Steps → target routes:
 *   0 (Movement) → map
 *   1 (Combat)   → map
 *   2 (Loot)     → (none — happens in fight screen)
 *   3 (Quests)   → quests
 *   4 (Craft)    → craft
 */
export default class extends Controller {
    static values = { step: Number }

    static STEP_ROUTES = {
        0: ['map'],
        1: ['map'],
        3: ['quests'],
        4: ['craft'],
    }

    connect() {
        this._applyHighlights()
    }

    disconnect() {
        document.querySelectorAll('.tutorial-highlight').forEach(el => {
            el.classList.remove('tutorial-highlight')
        })
    }

    _applyHighlights() {
        const routes = this.constructor.STEP_ROUTES[this.stepValue]
        if (!routes) return

        routes.forEach(route => {
            document.querySelectorAll(`[data-tutorial-route~="${route}"]`).forEach(el => {
                el.classList.add('tutorial-highlight')
            })
        })
    }
}
