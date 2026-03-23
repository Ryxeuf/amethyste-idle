import { Controller } from '@hotwired/stimulus';

/**
 * Dialog overlay controller for NPC conversations.
 * Renders an HTML dialog box over the map canvas.
 *
 * Features:
 * - Typewriter text animation with configurable speed
 * - Click/tap to skip or advance
 * - Choice buttons with action routing (close, quest, shop, branch)
 * - Keyboard support (Space/Enter to advance, Escape to close)
 * - Subtle open/close animations
 *
 * Listens for 'map_pixi:pnjDialog' Stimulus events from the map controller.
 */
export default class extends Controller {
    static targets = ['box', 'name', 'text', 'choices', 'nextBtn', 'portrait'];
    static values = {
        typeSpeed: { type: Number, default: 25 },
    };

    connect() {
        this._sentences = [];
        this._currentIndex = 0;
        this._typewriterTimer = null;
        this._isTyping = false;
        this._fullText = '';
        this._sentenceHistory = [];

        // Keyboard support for dialogs
        this._onKeyDown = (e) => this._handleDialogKey(e);
        document.addEventListener('keydown', this._onKeyDown);
    }

    disconnect() {
        this._stopTypewriter();
        if (this._onKeyDown) {
            document.removeEventListener('keydown', this._onKeyDown);
        }
    }

    // Called from map controller via Stimulus dispatch
    open(event) {
        const { sentences, pnjName, portrait, classType } = event.detail;
        if (!sentences || sentences.length === 0) return;

        this._sentences = sentences;
        this._currentIndex = 0;
        this._sentenceHistory = [];

        this.nameTarget.textContent = pnjName || 'PNJ';
        this._updatePortrait(portrait, classType);
        this.boxTarget.classList.remove('hidden');

        // Slide-up animation
        this.boxTarget.style.transform = 'translateY(10px)';
        this.boxTarget.style.opacity = '0';
        this.boxTarget.style.transition = 'transform 0.2s ease-out, opacity 0.2s ease-out';
        requestAnimationFrame(() => {
            this.boxTarget.style.transform = 'translateY(0)';
            this.boxTarget.style.opacity = '1';
        });

        this._showSentence(0);
    }

    close() {
        // Slide-down animation before hiding
        this.boxTarget.style.transform = 'translateY(10px)';
        this.boxTarget.style.opacity = '0';
        setTimeout(() => {
            this.boxTarget.classList.add('hidden');
            this.boxTarget.style.transform = '';
            this.boxTarget.style.opacity = '';
            this._stopTypewriter();
            this._sentences = [];
            this._currentIndex = 0;
            this._sentenceHistory = [];
            this.dispatch('closed');
        }, 200);
    }

    next() {
        // If still typing, show full text immediately
        if (this._isTyping) {
            this._stopTypewriter();
            this.textTarget.textContent = this._fullText;
            this._isTyping = false;
            // Show choices immediately if present
            const sentence = this._sentences[this._currentIndex];
            if (sentence?.choices?.length > 0) {
                this._renderChoices(sentence.choices);
            }
            return;
        }

        const sentence = this._sentences[this._currentIndex];
        if (!sentence) {
            this.close();
            return;
        }

        // If sentence has a next index, go there
        if (sentence.next !== undefined && sentence.next !== null) {
            this._sentenceHistory.push(this._currentIndex);
            this._showSentence(sentence.next);
        } else if (!sentence.choices) {
            // End of dialog
            this.close();
        }
    }

    /** Keyboard handler for dialog navigation */
    _handleDialogKey(e) {
        if (this.boxTarget.classList.contains('hidden')) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            this.close();
        } else if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            this.next();
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

        if (choice.action === 'quest_offer' && choice.datas?.quest) {
            this._acceptQuest(choice.datas.quest);
            this.close();
            return;
        }

        if (choice.action === 'open_shop' && choice.datas?.pnj_id) {
            window.location.href = `/game/shop/${choice.datas.pnj_id}`;
            return;
        }

        // If choice has a next index, navigate there
        if (choice.next !== undefined && choice.next !== null) {
            this._showSentence(choice.next);
            return;
        }

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

        const speed = this.typeSpeedValue;

        this._typewriterTimer = setInterval(() => {
            if (i < text.length) {
                this.textTarget.textContent += text[i];
                // Variable speed: pause longer on punctuation
                const char = text[i];
                if (char === '.' || char === '!' || char === '?') {
                    clearInterval(this._typewriterTimer);
                    this._typewriterTimer = null;
                    i++;
                    setTimeout(() => {
                        if (this._isTyping) this._startTypewriterFrom(text, i);
                    }, speed * 6);
                    return;
                }
                if (char === ',') {
                    clearInterval(this._typewriterTimer);
                    this._typewriterTimer = null;
                    i++;
                    setTimeout(() => {
                        if (this._isTyping) this._startTypewriterFrom(text, i);
                    }, speed * 3);
                    return;
                }
                i++;
            } else {
                this._stopTypewriter();
                this._isTyping = false;
            }
        }, speed);
    }

    /** Resume typewriter from a specific position */
    _startTypewriterFrom(text, startIndex) {
        let i = startIndex;
        const speed = this.typeSpeedValue;

        this._typewriterTimer = setInterval(() => {
            if (i < text.length) {
                this.textTarget.textContent += text[i];
                const char = text[i];
                if (char === '.' || char === '!' || char === '?') {
                    clearInterval(this._typewriterTimer);
                    this._typewriterTimer = null;
                    i++;
                    setTimeout(() => {
                        if (this._isTyping) this._startTypewriterFrom(text, i);
                    }, speed * 6);
                    return;
                }
                if (char === ',') {
                    clearInterval(this._typewriterTimer);
                    this._typewriterTimer = null;
                    i++;
                    setTimeout(() => {
                        if (this._isTyping) this._startTypewriterFrom(text, i);
                    }, speed * 3);
                    return;
                }
                i++;
            } else {
                this._stopTypewriter();
                this._isTyping = false;
            }
        }, speed);
    }

    _stopTypewriter() {
        if (this._typewriterTimer) {
            clearInterval(this._typewriterTimer);
            this._typewriterTimer = null;
        }
    }

    async _acceptQuest(questId) {
        try {
            const resp = await fetch(`/game/quests/accept/${questId}`, { method: 'POST' });
            const data = await resp.json();
            if (data.success) {
                console.debug('[dialog] Quest accepted:', data.message);
            }
        } catch (err) {
            console.error('[dialog] Quest accept error:', err);
        }
    }

    _showChoicesAfterText(choices) {
        // Wait for typewriter to finish, then show choices
        const checkReady = () => {
            if (this._isTyping) {
                setTimeout(checkReady, 100);
                return;
            }
            this._renderChoices(choices);
        };
        checkReady();
    }

    /** Class type to fallback icon mapping */
    static _classTypeIcons = {
        villager: '\u{1F9D1}',
        merchant: '\u{1F4B0}',
        guard: '\u{1F6E1}',
        noble: '\u{1F451}',
        warrior: '\u2694\uFE0F',
        mage: '\u{1F9D9}',
        healer: '\u{1FA7A}',
        blacksmith: '\u{1F528}',
        farmer: '\u{1F33E}',
        hunter: '\u{1F3F9}',
    };

    _updatePortrait(portrait, classType) {
        if (!this.hasPortraitTarget) return;

        if (portrait) {
            this.portraitTarget.innerHTML = `<img src="${portrait}" alt="" class="w-12 h-12 rounded-lg border border-purple-500/50 object-cover">`;
            this.portraitTarget.classList.remove('hidden');
        } else {
            const icon = this.constructor._classTypeIcons[classType] || '\u{1F9D1}';
            this.portraitTarget.innerHTML = `<span class="w-12 h-12 rounded-lg border border-purple-500/50 bg-gray-800 flex items-center justify-center text-2xl">${icon}</span>`;
            this.portraitTarget.classList.remove('hidden');
        }
    }

    /** Render choice buttons with staggered animation */
    _renderChoices(choices) {
        this.choicesTarget.classList.remove('hidden');
        this.choicesTarget.innerHTML = '';
        this.nextBtnTarget.classList.add('hidden');

        for (let i = 0; i < choices.length; i++) {
            const btn = document.createElement('button');
            btn.className = 'px-4 py-2 bg-purple-700 hover:bg-purple-600 text-white rounded text-sm transition-all duration-200 hover:scale-105';
            btn.textContent = choices[i].text;
            btn.dataset.choiceIndex = i;
            btn.dataset.action = 'click->dialog#selectChoice';

            // Staggered fade-in
            btn.style.opacity = '0';
            btn.style.transform = 'translateY(5px)';
            btn.style.transition = 'opacity 0.2s ease, transform 0.2s ease';

            btn.addEventListener('click', (e) => {
                e.currentTarget.dataset.choiceIndex = i;
                this.selectChoice(e);
            });
            this.choicesTarget.appendChild(btn);

            // Trigger animation after a staggered delay
            setTimeout(() => {
                btn.style.opacity = '1';
                btn.style.transform = 'translateY(0)';
            }, i * 80);
        }
    }
}
