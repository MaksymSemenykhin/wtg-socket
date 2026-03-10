/**
 * Alpine.js messenger component for the dashboard chat.
 * Registered before Alpine.start() so x-data="messenger(...)" resolves.
 */
export default function registerMessenger(Alpine) {
    Alpine.data('messenger', (currentUserId, usersList) => ({
        currentUserId,
        users: usersList,
        selectedUser: null,
        messages: [],
        newMessage: '',
        echoChannel: null,
        _countedUnreadIds: null,

        init() {
            this._countedUnreadIds = new Set();
            this.users.forEach((u) => {
                if (u.unread_count === undefined) u.unread_count = 0;
            });
            if (window.Echo) {
                const self = this;
                const channelName = `user.${self.currentUserId}`;
                window.Echo.private(channelName).listen('.MessageSent', (e) => {
                    const data = typeof e?.data === 'string' ? (() => { try { return JSON.parse(e.data); } catch { return e; } })() : (e?.data ?? e);
                    const raw = data?.message ?? data;
                    if (!raw || typeof raw !== 'object' || (raw.sender_id == null && raw.id == null)) return;
                    const senderId = Number(raw.sender_id);
                    const recipientId = Number(raw.recipient_id);
                    const isInThisChat =
                        self.selectedUser &&
                        (recipientId === Number(self.selectedUser.id) || senderId === Number(self.selectedUser.id));
                    if (isInThisChat) {
                        const msgId = raw.id != null ? raw.id : `e-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
                        const alreadyExists = self.messages.some((m) => String(m.id) === String(msgId) || String(m._key) === String(msgId));
                        if (alreadyExists) return;
                        const msg = {
                            id: msgId,
                            _key: `msg-${msgId}-${Date.now()}`,
                            sender_id: senderId,
                            recipient_id: recipientId,
                            body: String(raw.body ?? ''),
                            created_at: raw.created_at ?? new Date().toISOString(),
                            sender: raw.sender && typeof raw.sender === 'object' ? { id: raw.sender.id, name: String(raw.sender.name ?? ''), email: String(raw.sender.email ?? '') } : { id: senderId, name: '', email: '' },
                            recipient: raw.recipient && typeof raw.recipient === 'object' ? { id: raw.recipient.id, name: String(raw.recipient.name ?? ''), email: String(raw.recipient.email ?? '') } : { id: recipientId, name: '', email: '' },
                        };
                        self.messages = [...self.messages, msg];
                        self.$nextTick(() => self.scrollToBottom());
                    } else if (recipientId === Number(self.currentUserId)) {
                        const msgId = raw.id != null ? String(raw.id) : null;
                        if (msgId && self._countedUnreadIds.has(msgId)) return;
                        if (msgId) self._countedUnreadIds.add(msgId);
                        const sender = self.users.find((u) => Number(u.id) === senderId);
                        if (sender) sender.unread_count = (sender.unread_count || 0) + 1;
                    }
                });
            }
        },

        selectUser(user) {
            this.selectedUser = user;
            this.messages = [];
            this.newMessage = '';
            if (user.unread_count !== undefined) user.unread_count = 0;
            fetch(`/messages/${user.id}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((r) => r.json())
                .then((data) => {
                    const list = (data.messages || []).map((m) => ({ ...m, _key: m._key ?? m.id ?? `msg-${m.sender_id}-${m.created_at}-${Math.random().toString(36).slice(2, 9)}` }));
                    this.messages = list;
                    if (data.unread_counts) {
                        this.users.forEach((u) => {
                            u.unread_count = data.unread_counts[u.id] ?? 0;
                        });
                    }
                    this.$nextTick(() => this.scrollToBottom());
                })
                .catch((err) => console.error(err));
        },

        sendMessage() {
            const body = this.newMessage.trim();
            if (!body || !this.selectedUser) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrf) return;
            fetch('/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ recipient_id: this.selectedUser.id, body }),
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.message) {
                        const m = { ...data.message, _key: data.message._key ?? data.message.id ?? `sent-${Date.now()}` };
                        this.messages.push(m);
                        this.newMessage = '';
                        this.$nextTick(() => this.scrollToBottom());
                    }
                })
                .catch((err) => console.error(err));
        },

        formatTime(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleString();
        },

        scrollToBottom() {
            const el = document.getElementById('messages-container');
            if (el) el.scrollTop = el.scrollHeight;
        },
    }));
}
