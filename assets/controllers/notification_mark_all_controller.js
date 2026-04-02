import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };

    async markAll(event) {
        event.preventDefault();

        try {
            const resp = await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok) {
                window.location.reload();
            }
        } catch {
            // Silently ignore
        }
    }
}
