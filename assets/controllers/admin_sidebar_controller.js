import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['sidebar', 'overlay']

    toggle() {
        this.sidebarTarget.classList.toggle('-translate-x-full')
        this.overlayTarget.classList.toggle('hidden')
        document.body.classList.toggle('overflow-hidden')
    }

    close() {
        this.sidebarTarget.classList.add('-translate-x-full')
        this.overlayTarget.classList.add('hidden')
        document.body.classList.remove('overflow-hidden')
    }
}
