import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        urlTemplate: String,
    };

    change(event) {
        const locale = event.target.value;
        if (!locale) {
            return;
        }

        const url = this.urlTemplateValue.replace('__LOCALE__', encodeURIComponent(locale));
        window.location.href = url;
    }
}
