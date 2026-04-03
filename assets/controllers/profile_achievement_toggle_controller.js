import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };

    async connect() {
        this.element.addEventListener('click', this._toggle.bind(this));
    }

    async _toggle(event) {
        event.preventDefault();

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json();
                if (data.error) {
                    alert(data.error);
                }
                return;
            }

            // Reload the page to reflect changes
            window.location.reload();
        } catch (err) {
            console.error('Toggle featured failed:', err);
        }
    }
}
