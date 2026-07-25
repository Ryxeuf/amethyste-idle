import { Controller } from '@hotwired/stimulus';

/**
 * Chat de zone temps reel (pivot PBBG, ZON-14).
 *
 * S'abonne au topic Mercure `chat/zone/<id>` pour afficher les messages en
 * direct, envoie via /game/chat/send (canal `zone`), et rafraichit la liste de
 * presence sur activite + periodiquement (near real-time sans suivi de
 * join/leave cote serveur).
 */
export default class extends Controller {
    static targets = ['messages', 'input', 'presence'];
    static values = {
        mercureUrl: String,
        zoneId: Number,
        sendUrl: String,
        presenceUrl: String,
        inviteUrl: String,
        profileUrl: String,
    };

    connect() {
        this._topic = `chat/zone/${this.zoneIdValue}`;

        if (this.mercureUrlValue) {
            const url = new URL(this.mercureUrlValue);
            url.searchParams.append('topic', this._topic);
            this._eventSource = new EventSource(url);
            this._eventSource.onmessage = (event) => {
                let data;
                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    return;
                }
                if (data.type === 'chat_message') {
                    this._appendMessage(data);
                    this._refreshPresence();
                }
            };
        }

        this._scrollToBottom();
        this._presenceTimer = window.setInterval(() => this._refreshPresence(), 20000);
    }

    disconnect() {
        if (this._eventSource) {
            this._eventSource.close();
            this._eventSource = null;
        }
        if (this._presenceTimer) {
            window.clearInterval(this._presenceTimer);
            this._presenceTimer = null;
        }
    }

    async send(event) {
        event.preventDefault();
        if (!this.hasInputTarget) return;

        const content = this.inputTarget.value.trim();
        if (content === '') return;

        this.inputTarget.value = '';

        try {
            const body = new URLSearchParams();
            body.append('content', content);
            body.append('channel', 'zone');
            await fetch(this.sendUrlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
        } catch (e) {
            // silencieux : le message n'a pas ete envoye, l'utilisateur peut reessayer
        }
    }

    _appendMessage(data) {
        if (!this.hasMessagesTarget) return;

        const line = document.createElement('div');
        line.className = 'text-xs py-0.5';

        const time = document.createElement('span');
        time.className = 'text-gray-600 mr-1';
        time.textContent = data.createdAt || '';

        const name = document.createElement('span');
        name.className = 'font-semibold text-sky-300 mr-1';
        name.textContent = `${data.sender?.name ?? '?'}:`;

        const content = document.createElement('span');
        content.className = 'text-gray-200';
        content.textContent = data.content ?? '';

        line.appendChild(time);
        line.appendChild(name);
        line.appendChild(content);
        this.messagesTarget.appendChild(line);
        this._scrollToBottom();
    }

    async _refreshPresence() {
        if (!this.hasPresenceTarget || !this.presenceUrlValue) return;

        let data;
        try {
            const res = await fetch(this.presenceUrlValue, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            data = await res.json();
        } catch (e) {
            return;
        }

        const players = data.players || [];
        this.presenceTarget.innerHTML = '';

        players.forEach((p) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2 py-0.5';

            const nameLink = document.createElement('a');
            nameLink.className = 'text-xs text-gray-200 hover:text-white truncate';
            nameLink.textContent = p.name + (p.self ? ' •' : '');
            if (this.profileUrlValue) {
                nameLink.href = this.profileUrlValue.replace('__ID__', p.id);
            }
            row.appendChild(nameLink);

            if (!p.self && this.inviteUrlValue) {
                const invite = document.createElement('button');
                invite.type = 'button';
                invite.className = 'text-[10px] px-1.5 py-0.5 rounded bg-indigo-900/50 border border-indigo-700 text-indigo-200 hover:bg-indigo-800/60';
                invite.textContent = '+';
                invite.title = 'Inviter dans le groupe';
                invite.addEventListener('click', () => this._invite(p.id));
                row.appendChild(invite);
            }

            this.presenceTarget.appendChild(row);
        });
    }

    async _invite(playerId) {
        if (!this.inviteUrlValue) return;
        try {
            await fetch(this.inviteUrlValue.replace('__ID__', playerId), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
        } catch (e) {
            // silencieux
        }
    }

    _scrollToBottom() {
        if (this.hasMessagesTarget) {
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
        }
    }
}
