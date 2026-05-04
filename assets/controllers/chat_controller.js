import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'messages', 'input', 'form', 'tab',
        'globalPanel', 'mapPanel', 'guildPanel', 'privatePanel',
        'conversationList', 'privateMessages', 'privateTo',
        'playerSearch', 'playerResults', 'badge',
    ];
    static values = {
        mercureUrl: String,
        playerId: Number,
        playerMapId: Number,
        guildId: { type: Number, default: 0 },
        sendUrl: String,
        historyUrl: String,
        conversationsUrl: String,
        searchUrl: String,
        profileUrl: String,
        labels: { type: Object, default: {} },
    };

    _label(key, fallback) {
        const value = this.labelsValue?.[key];
        return typeof value === 'string' && value.length > 0 ? value : fallback;
    }

    connect() {
        this._activeChannel = 'global';
        this._privateRecipientId = null;
        this._privateRecipientName = null;
        this._eventSource = null;
        this._searchTimeout = null;

        this._connectMercure();
        this._scrollAllToBottom();
    }

    disconnect() {
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
    }

    switchTab(event) {
        const channel = event.currentTarget.dataset.channel;
        this._activeChannel = channel;

        this.tabTargets.forEach(tab => {
            tab.classList.toggle('chat-tab-active', tab.dataset.channel === channel);
            tab.classList.toggle('chat-tab-inactive', tab.dataset.channel !== channel);
        });

        if (this.hasGlobalPanelTarget) this.globalPanelTarget.classList.toggle('hidden', channel !== 'global');
        if (this.hasMapPanelTarget) this.mapPanelTarget.classList.toggle('hidden', channel !== 'map');
        if (this.hasGuildPanelTarget) this.guildPanelTarget.classList.toggle('hidden', channel !== 'guild');
        if (this.hasPrivatePanelTarget) this.privatePanelTarget.classList.toggle('hidden', channel !== 'private');

        if (channel !== 'private') {
            this._privateRecipientId = null;
            this._privateRecipientName = null;
        }
    }

    async send(event) {
        event.preventDefault();

        const input = this.inputTarget;
        const content = input.value.trim();
        if (!content) return;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('channel', this._activeChannel);

        if (this._activeChannel === 'private' && this._privateRecipientId) {
            formData.append('recipient_id', this._privateRecipientId);
        }

        input.value = '';
        input.focus();

        try {
            const response = await fetch(this.sendUrlValue, {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                this._appendSystemMessage(data.error || this._label('send_error', 'Erreur lors de l\'envoi.'));
            } else if (data.system && data.systemMessage) {
                this._appendSystemMessage(data.systemMessage);
            }
        } catch (err) {
            console.error('Chat send failed:', err);
        }
    }

    async selectConversation(event) {
        const playerId = parseInt(event.currentTarget.dataset.playerId);
        const playerName = event.currentTarget.dataset.playerName;

        this._privateRecipientId = playerId;
        this._privateRecipientName = playerName;

        if (this.hasPrivateToTarget) {
            this.privateToTarget.textContent = playerName;
        }

        try {
            const url = `${this.historyUrlValue}/private?with=${playerId}`;
            const response = await fetch(url);
            const data = await response.json();

            if (this.hasPrivateMessagesTarget) {
                this.privateMessagesTarget.innerHTML = '';
                data.messages.forEach(msg => this._appendMessage(this.privateMessagesTarget, msg));
                this._scrollToBottom(this.privateMessagesTarget);
            }
        } catch (err) {
            console.error('Failed to load private messages:', err);
        }
    }

    searchPlayers(event) {
        const query = event.currentTarget.value.trim();
        clearTimeout(this._searchTimeout);

        if (query.length < 2) {
            if (this.hasPlayerResultsTarget) this.playerResultsTarget.innerHTML = '';
            return;
        }

        this._searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`${this.searchUrlValue}?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (this.hasPlayerResultsTarget) {
                    this.playerResultsTarget.innerHTML = data.players.map(p => `
                        <button type="button"
                                data-action="chat#startPrivateChat"
                                data-player-id="${p.id}"
                                data-player-name="${p.name}"
                                class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded transition-colors">
                            ${this._escapeHtml(p.name)}
                        </button>
                    `).join('');
                }
            } catch (err) {
                console.error('Player search failed:', err);
            }
        }, 300);
    }

    startPrivateChat(event) {
        const playerId = parseInt(event.currentTarget.dataset.playerId);
        const playerName = event.currentTarget.dataset.playerName;

        this._privateRecipientId = playerId;
        this._privateRecipientName = playerName;

        if (this.hasPrivateToTarget) {
            this.privateToTarget.textContent = playerName;
        }

        if (this.hasPlayerResultsTarget) this.playerResultsTarget.innerHTML = '';
        if (this.hasPlayerSearchTarget) this.playerSearchTarget.value = '';
        if (this.hasPrivateMessagesTarget) this.privateMessagesTarget.innerHTML = '';

        this.selectConversation({ currentTarget: { dataset: { playerId, playerName } } });
    }

    _connectMercure() {
        if (!this.mercureUrlValue) return;

        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', 'chat/global');
        if (this.playerMapIdValue) {
            url.searchParams.append('topic', `chat/map/${this.playerMapIdValue}`);
        }
        if (this.guildIdValue) {
            url.searchParams.append('topic', `chat/guild/${this.guildIdValue}`);
        }
        url.searchParams.append('topic', `chat/private/${this.playerIdValue}`);

        this._eventSource = new EventSource(url);
        this._eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this._handleMercureEvent(data);
        };
    }

    _handleMercureEvent(data) {
        if (data.type === 'chat_message') {
            this._handleNewMessage(data);
        } else if (data.type === 'chat_delete') {
            this._handleDeleteMessage(data);
        }
    }

    _handleNewMessage(data) {
        const { channel, topic } = data;

        if (channel === 'global' && this.hasGlobalPanelTarget) {
            const container = this.globalPanelTarget.querySelector('[data-chat-target="messages"]')
                || this.globalPanelTarget.querySelector('.chat-messages');
            if (container) {
                this._appendMessage(container, data);
                this._scrollToBottom(container);
            }
        }

        if (channel === 'map' && this.hasMapPanelTarget) {
            const container = this.mapPanelTarget.querySelector('[data-chat-target="messages"]')
                || this.mapPanelTarget.querySelector('.chat-messages');
            if (container) {
                this._appendMessage(container, data);
                this._scrollToBottom(container);
            }
        }

        if (channel === 'guild' && this.hasGuildPanelTarget) {
            const container = this.guildPanelTarget.querySelector('[data-chat-target="messages"]')
                || this.guildPanelTarget.querySelector('.chat-messages');
            if (container) {
                this._appendMessage(container, data);
                this._scrollToBottom(container);
            }
        }

        if (channel === 'private') {
            const otherId = data.sender.id === this.playerIdValue
                ? data.recipient?.id
                : data.sender.id;

            if (otherId === this._privateRecipientId && this.hasPrivateMessagesTarget) {
                this._appendMessage(this.privateMessagesTarget, data);
                this._scrollToBottom(this.privateMessagesTarget);
            }
        }
    }

    _handleDeleteMessage(data) {
        const el = this.element.querySelector(`[data-message-id="${data.messageId}"]`);
        if (el) {
            el.classList.add('opacity-50', 'line-through');
            el.querySelector('.chat-content')?.replaceWith(
                Object.assign(document.createElement('span'), {
                    className: 'chat-content text-gray-500 italic',
                    textContent: this._label('deleted_message', '[message supprime]'),
                })
            );
        }
    }

    _appendMessage(container, data) {
        const isSelf = data.sender.id === this.playerIdValue;
        const div = document.createElement('div');
        div.className = 'chat-line py-0.5 px-2';
        div.dataset.messageId = data.id;

        const time = document.createElement('span');
        time.className = 'text-gray-500 text-xs mr-1';
        time.textContent = `[${data.createdAt}]`;

        // Guild tag (shown on global and map channels)
        if (data.sender.guildTag && data.channel !== 'guild' && data.channel !== 'private') {
            const tag = document.createElement('span');
            tag.className = 'text-xs font-bold mr-0.5';
            if (data.sender.guildColor) {
                tag.style.color = data.sender.guildColor;
            } else {
                tag.classList.add('text-amber-400');
            }
            tag.textContent = `[${this._escapeHtml(data.sender.guildTag)}]`;
            div.appendChild(time);
            div.appendChild(tag);
        } else {
            div.appendChild(time);
        }

        // Prestige title
        if (data.sender.prestigeTitle) {
            const title = document.createElement('span');
            title.className = 'text-yellow-300 text-xs italic mr-0.5';
            title.textContent = this._escapeHtml(data.sender.prestigeTitle);
            div.appendChild(title);
        }

        const name = document.createElement('a');
        name.href = this.profileUrlValue.replace('__ID__', data.sender.id);
        name.className = `font-semibold text-sm mr-1 hover:underline ${isSelf ? 'text-purple-400' : 'text-blue-400'}`;

        if (data.channel === 'private') {
            const target = this._escapeHtml(isSelf ? (data.recipient?.name || '?') : data.sender.name);
            const template = isSelf
                ? this._label('private_outgoing', '[MP -> %name%]')
                : this._label('private_incoming', '[MP de %name%]');
            name.textContent = template.replace('%name%', target);
            name.className = 'font-semibold text-sm mr-1 hover:underline text-pink-400';
        } else if (data.channel === 'guild') {
            name.textContent = `${this._escapeHtml(data.sender.name)}:`;
            name.className = `font-semibold text-sm mr-1 hover:underline ${isSelf ? 'text-purple-400' : 'text-emerald-400'}`;
        } else {
            name.textContent = `${this._escapeHtml(data.sender.name)}:`;
        }

        const content = document.createElement('span');
        content.className = 'chat-content text-sm text-gray-200';
        content.textContent = ` ${data.content}`;

        div.appendChild(name);
        div.appendChild(content);
        container.appendChild(div);
    }

    _scrollAllToBottom() {
        this.element.querySelectorAll('.chat-messages').forEach(el => this._scrollToBottom(el));
    }

    _scrollToBottom(el) {
        if (el) el.scrollTop = el.scrollHeight;
    }

    _appendSystemMessage(text) {
        const container = this._getActiveContainer();
        if (!container) return;

        const lines = text.split('\n');
        lines.forEach(line => {
            const div = document.createElement('div');
            div.className = 'chat-line py-0.5 px-2';

            const content = document.createElement('span');
            content.className = 'text-sm text-yellow-400 italic';
            content.textContent = line;

            div.appendChild(content);
            container.appendChild(div);
        });

        this._scrollToBottom(container);
    }

    _getActiveContainer() {
        const panelMap = {
            global: this.hasGlobalPanelTarget ? this.globalPanelTarget : null,
            map: this.hasMapPanelTarget ? this.mapPanelTarget : null,
            guild: this.hasGuildPanelTarget ? this.guildPanelTarget : null,
            private: this.hasPrivateMessagesTarget ? this.privateMessagesTarget : null,
        };

        const panel = panelMap[this._activeChannel];
        if (!panel) return null;

        if (this._activeChannel === 'private') return panel;
        return panel.querySelector('[data-chat-target="messages"]')
            || panel.querySelector('.chat-messages');
    }

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
