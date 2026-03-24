import { Controller } from '@hotwired/stimulus'

/**
 * Handles desktop dropdown menus and mobile "more" drawer.
 *
 * Desktop: hover/click to open dropdowns, click outside to close.
 * Mobile: tap "Plus" to toggle a slide-up drawer with extra nav items.
 */
export default class extends Controller {
    static targets = ['dropdown', 'mobileDrawer', 'mobileOverlay']

    connect() {
        this._onClickOutside = this._onClickOutside.bind(this)
        document.addEventListener('click', this._onClickOutside)
    }

    disconnect() {
        document.removeEventListener('click', this._onClickOutside)
    }

    // ---- Desktop dropdowns ----

    toggleDropdown(event) {
        const btn = event.currentTarget
        const dropdown = btn.closest('[data-dropdown-group]')
        const menu = dropdown.querySelector('[data-game-nav-target="dropdown"]')

        const wasOpen = !menu.classList.contains('hidden')

        // Close all dropdowns first
        this._closeAllDropdowns()

        if (!wasOpen) {
            menu.classList.remove('hidden')
            requestAnimationFrame(() => {
                menu.classList.remove('opacity-0', 'translate-y-1')
                menu.classList.add('opacity-100', 'translate-y-0')
            })
        }
    }

    _closeAllDropdowns() {
        this.dropdownTargets.forEach(menu => {
            menu.classList.add('opacity-0', 'translate-y-1')
            menu.classList.remove('opacity-100', 'translate-y-0')
            // Hide after transition
            setTimeout(() => {
                if (menu.classList.contains('opacity-0')) {
                    menu.classList.add('hidden')
                }
            }, 150)
        })
    }

    _onClickOutside(event) {
        // If click is outside any dropdown group, close all
        const isInsideDropdown = event.target.closest('[data-dropdown-group]')
        if (!isInsideDropdown) {
            this._closeAllDropdowns()
        }

        // If click is outside mobile drawer and overlay, close drawer
        if (this.hasMobileDrawerTarget && !event.target.closest('[data-mobile-drawer-area]')) {
            this.closeMobileDrawer()
        }
    }

    // ---- Mobile drawer ----

    toggleMobileDrawer() {
        if (!this.hasMobileDrawerTarget) return

        const drawer = this.mobileDrawerTarget
        const overlay = this.mobileOverlayTarget
        const isOpen = !drawer.classList.contains('translate-y-full')

        if (isOpen) {
            this.closeMobileDrawer()
        } else {
            overlay.classList.remove('hidden')
            drawer.classList.remove('translate-y-full')
            requestAnimationFrame(() => {
                overlay.classList.remove('opacity-0')
                overlay.classList.add('opacity-100')
            })
        }
    }

    closeMobileDrawer() {
        if (!this.hasMobileDrawerTarget) return

        const drawer = this.mobileDrawerTarget
        const overlay = this.mobileOverlayTarget

        drawer.classList.add('translate-y-full')
        overlay.classList.add('opacity-0')
        overlay.classList.remove('opacity-100')
        setTimeout(() => overlay.classList.add('hidden'), 200)
    }
}
