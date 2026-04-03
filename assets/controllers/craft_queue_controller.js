import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['quantityInput', 'button', 'buttonText', 'maxBadge'];
    static values = {
        slug: String,
        max: Number,
    };

    connect() {
        this._crafting = false;
    }

    increment() {
        const input = this.quantityInputTarget;
        const current = parseInt(input.value, 10) || 1;
        if (current < this.maxValue) {
            input.value = current + 1;
        }
    }

    decrement() {
        const input = this.quantityInputTarget;
        const current = parseInt(input.value, 10) || 1;
        if (current > 1) {
            input.value = current - 1;
        }
    }

    setMax() {
        this.quantityInputTarget.value = this.maxValue;
    }

    async craft(event) {
        event.preventDefault();

        if (this._crafting) return;
        this._crafting = true;

        const quantity = Math.max(1, Math.min(parseInt(this.quantityInputTarget.value, 10) || 1, this.maxValue));
        this.quantityInputTarget.value = quantity;

        this.buttonTarget.disabled = true;
        this.buttonTextTarget.textContent = `Fabrication... (0/${quantity})`;

        try {
            const resp = await fetch(`/api/craft/batch/${this.slugValue}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quantity }),
            });

            const data = await resp.json();

            if (data.success) {
                this.buttonTextTarget.textContent = `${data.crafted}/${quantity} fabriques !`;
                if (window.Toast) {
                    window.Toast.show('success', data.message);
                }
            } else {
                this.buttonTextTarget.textContent = 'Fabriquer';
                if (window.Toast) {
                    window.Toast.show('warning', data.message);
                }
            }

            // Recharger la page apres un court delai pour mettre a jour l'inventaire
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } catch {
            this.buttonTextTarget.textContent = 'Fabriquer';
            if (window.Toast) {
                window.Toast.show('error', 'Erreur de connexion.');
            }
            this.buttonTarget.disabled = false;
            this._crafting = false;
        }
    }
}
