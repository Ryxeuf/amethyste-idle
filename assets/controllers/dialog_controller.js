import { Controller } from '@hotwired/stimulus';

/**
 * Dialog overlay controller for NPC conversations.
 * Renders an HTML dialog box over the map canvas.
 *
 * Listens for 'map_pixi:pnjDialog' Stimulus events from the map controller.
 */
export default class extends Controller {
    static targets = ['box', 'name', 'text', 'choices', 'nextBtn'];

    connect() {
        this._sentences = [];
        this._currentIndex = 0;
        this._typewriterTimer = null;
        this._isTyping = false;
        this._fullText = '';
    }

    disconnect() {
        this._stopTypewriter();
    }

    // Called from map controller via Stimulus dispatch
    open(event) {
        const { sentences, pnjName } = event.detail;
        if (!sentences || sentences.length === 0) return;

        this._sentences = sentences;
        this._currentIndex = 0;

        this.nameTarget.textContent = pnjName || 'PNJ';
        this.boxTarget.classList.remove('hidden');
        this._showSentence(0);
    }

    close() {
        this.boxTarget.classList.add('hidden');
        this._stopTypewriter();
        this._sentences = [];
        this._currentIndex = 0;
        this.dispatch('closed');
    }

    next() {
        // If still typing, show full text immediately
        if (this._isTyping) {
            this._stopTypewriter();
            this.textTarget.textContent = this._fullText;
            this._isTyping = false;
            return;
        }

        const sentence = this._sentences[this._currentIndex];
        if (!sentence) {
            this.close();
            return;
        }

        // If sentence has a next index, go there
        if (sentence.next !== undefined && sentence.next !== null) {
            this._showSentence(sentence.next);
        } else if (!sentence.choices) {
            // End of dialog
            this.close();
        }
    }

    selectChoice(event) {
        const choiceIndex = parseInt(event.currentTarget.dataset.choiceIndex);
        const sentence = this._sentences[this._currentIndex];
        if (!sentence || !sentence.choices) return;

        const choice = sentence.choices[choiceIndex];
        if (!choice) return;

        if (choice.action === 'close') {
            this.close();
            return;
        }

        if (choice.action === 'quest_offer' && choice.data?.quest) {
            this.dispatch('questOffer', { detail: { questId: choice.data.quest } });
            this.close();
            return;
        }

        // Default: close dialog
        this.close();
    }

    // --- Private ---

    _showSentence(index) {
        if (index < 0 || index >= this._sentences.length) {
            this.close();
            return;
        }

        this._currentIndex = index;
        const sentence = this._sentences[index];

        // Clear choices
        this.choicesTarget.innerHTML = '';
        this.choicesTarget.classList.add('hidden');
        this.nextBtnTarget.classList.remove('hidden');

        // Typewriter effect
        this._fullText = sentence.text || '';
        this._startTypewriter(this._fullText);

        // Show choices if present (after text is done)
        if (sentence.choices && sentence.choices.length > 0) {
            this.nextBtnTarget.classList.add('hidden');
            this._showChoicesAfterText(sentence.choices);
        }
    }

    _startTypewriter(text) {
        this._stopTypewriter();
        this._isTyping = true;
        this.textTarget.textContent = '';
        let i = 0;

        this._typewriterTimer = setInterval(() => {
            if (i < text.length) {
                this.textTarget.textContent += text[i];
                i++;
            } else {
                this._stopTypewriter();
                this._isTyping = false;
            }
        }, 30);
    }

    _stopTypewriter() {
        if (this._typewriterTimer) {
            clearInterval(this._typewriterTimer);
            this._typewriterTimer = null;
        }
    }

    _showChoicesAfterText(choices) {
        // Wait for typewriter to finish, then show choices
        const checkReady = () => {
            if (this._isTyping) {
                setTimeout(checkReady, 100);
                return;
            }
            this.choicesTarget.classList.remove('hidden');
            this.choicesTarget.innerHTML = '';

            for (let i = 0; i < choices.length; i++) {
                const btn = document.createElement('button');
                btn.className = 'px-4 py-2 bg-purple-700 hover:bg-purple-600 text-white rounded text-sm transition-colors';
                btn.textContent = choices[i].text;
                btn.dataset.choiceIndex = i;
                btn.dataset.action = 'click->dialog#selectChoice';
                btn.addEventListener('click', (e) => {
                    e.currentTarget.dataset.choiceIndex = i;
                    this.selectChoice(e);
                });
                this.choicesTarget.appendChild(btn);
            }
        };
        checkReady();
    }
}
