/**
 * Alpine.js chat application component.
 * Handles conversations, real-time messaging, typing indicators, and online status.
 */
export function chatApp() {
    return {
        // ── State ───────────────────────────────────────────────────────────
        currentUser:        null,
        conversations:      [],
        activeConversation: null,
        messages:           [],
        groupedMessages:    [],
        newMessage:         '',

        // Realtime / Echo
        onlineUsers:        [],  // array of user IDs currently online
        isOtherTyping:      false,
        typingTimer:        null,
        typingDebounce:     null,
        echoChannel:        null,
        presenceChannel:    null,

        // Loading states
        isLoadingConversations: false,
        isLoadingMessages:      false,
        isLoadingMore:          false,
        isSending:              false,

        // Pagination
        hasMoreMessages:    false,
        nextCursor:         null,

        // Attachment
        attachment:         null,
        attachmentPreview:  null,
        attachmentType:     null,
        attachmentName:     null,
        attachmentSize:     null,
        isUploading:        false,
        uploadProgress:     0,

        // User search
        searchQuery:        '',
        searchResults:      [],
        isSearching:        false,
        isSearchFocused:    false,

        // ── Lifecycle ────────────────────────────────────────────────────────
        init() {
            const data          = window.__CHAT_DATA__;
            this.currentUser    = data.currentUser;
            this.conversations  = data.conversations;

            this.setupPresenceChannel();
            this.$watch('messages', () => this.rebuildGroups());
        },

        // ── Presence channel (online status) ─────────────────────────────────
        setupPresenceChannel() {
            try {
                this.presenceChannel = window.Echo.join('online')
                    .here((users) => {
                        this.onlineUsers = users.map((u) => u.id);
                        this.syncOnlineStatus();
                    })
                    .joining((user) => {
                        if (!this.onlineUsers.includes(user.id)) {
                            this.onlineUsers = [...this.onlineUsers, user.id];
                        }
                        this.syncOnlineStatus();
                    })
                    .leaving((user) => {
                        this.onlineUsers = this.onlineUsers.filter((id) => id !== user.id);
                        this.syncOnlineStatus();
                    })
                    .error((err) => {
                        console.warn('Presence channel error:', err);
                    });
            } catch (err) {
                console.warn('Could not join presence channel:', err);
            }
        },

        syncOnlineStatus() {
            this.conversations.forEach((conv) => {
                if (conv.other_user) {
                    conv.other_user.is_online = this.onlineUsers.includes(conv.other_user.id);
                }
            });
            if (this.activeConversation?.other_user) {
                this.activeConversation.other_user.is_online =
                    this.onlineUsers.includes(this.activeConversation.other_user.id);
            }
        },

        // ── Conversation list ─────────────────────────────────────────────────
        async selectConversation(conversation) {
            if (this.activeConversation?.id === conversation.id) return;

            // Leave old channel
            if (this.echoChannel) {
                window.Echo.leave('conversation.' + this.activeConversation.id);
                this.echoChannel  = null;
                this.isOtherTyping = false;
            }

            this.activeConversation = conversation;
            this.messages           = [];
            this.groupedMessages    = [];
            this.hasMoreMessages    = false;
            this.nextCursor         = null;
            this.isOtherTyping      = false;

            // Reset unread in the list
            const idx = this.conversations.findIndex((c) => c.id === conversation.id);
            if (idx !== -1) this.conversations[idx].unread_count = 0;

            await this.loadMessages();
            this.joinConversationChannel(conversation.id);
            this.markRead(conversation.id);
        },

        sortConversations() {
            this.conversations.sort((a, b) => {
                const ta = a.last_message_at ? new Date(a.last_message_at) : new Date(a.created_at);
                const tb = b.last_message_at ? new Date(b.last_message_at) : new Date(b.created_at);
                return tb - ta;
            });
        },

        // ── Messages ──────────────────────────────────────────────────────────
        async loadMessages(append = false) {
            if (!this.activeConversation) return;
            if (append) {
                this.isLoadingMore = true;
            } else {
                this.isLoadingMessages = true;
            }

            try {
                const params  = new URLSearchParams();
                if (append && this.nextCursor) params.set('cursor', this.nextCursor);

                const res  = await window.axios.get(
                    `/conversations/${this.activeConversation.id}/messages?${params}`
                );
                const data = res.data;

                // Messages arrive newest-first; reverse to display oldest-first
                const newMsgs = [...data.messages].reverse();

                if (append) {
                    const container    = document.getElementById('messages-container');
                    const prevHeight   = container.scrollHeight;
                    this.messages      = [...newMsgs, ...this.messages];
                    this.$nextTick(() => {
                        container.scrollTop = container.scrollHeight - prevHeight;
                    });
                } else {
                    this.messages = newMsgs;
                    this.$nextTick(() => this.scrollToBottom());
                }

                this.hasMoreMessages = data.has_more;
                this.nextCursor      = data.next_cursor;
            } catch (err) {
                console.error('Failed to load messages', err);
            } finally {
                this.isLoadingMessages = false;
                this.isLoadingMore     = false;
            }
        },

        async sendMessage() {
            if (this.isSending) return;
            if (!this.newMessage.trim() && !this.attachment) return;
            if (!this.activeConversation) return;

            this.isSending = true;

            const formData = new FormData();
            if (this.newMessage.trim()) formData.append('message', this.newMessage.trim());
            if (this.attachment)        formData.append('attachment', this.attachment);

            const optimisticText = this.newMessage.trim();
            this.newMessage      = '';
            this.autoResize(this.$refs.messageInput);

            try {
                const res = await window.axios.post(
                    `/conversations/${this.activeConversation.id}/messages`,
                    formData,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (e) => {
                            this.uploadProgress = Math.round((e.loaded * 100) / e.total);
                        },
                    }
                );

                this.pushMessage(res.data.message);
                this.updateConversationPreview(this.activeConversation.id, res.data.message);
                this.clearAttachment();
                this.$nextTick(() => this.scrollToBottom());
            } catch (err) {
                console.error('Failed to send message', err);
                this.newMessage = optimisticText; // restore on error
            } finally {
                this.isSending      = false;
                this.uploadProgress = 0;
            }
        },

        pushMessage(message) {
            // Avoid duplicates (own message already pushed, echo arrives for others)
            if (this.messages.find((m) => m.id === message.id)) return;
            this.messages.push(message);
        },

        // ── Real-time channel ─────────────────────────────────────────────────
        joinConversationChannel(conversationId) {
            this.echoChannel = window.Echo.private('conversation.' + conversationId)
                .listen('.message.sent', (e) => {
                    // Only add if it's the active conversation
                    if (this.activeConversation?.id === e.message.conversation_id) {
                        this.pushMessage(e.message);
                        this.isOtherTyping = false;
                        this.$nextTick(() => this.scrollToBottom());
                        this.markRead(conversationId);
                    }
                    // Always update the conversation list
                    this.updateConversationPreview(e.conversation.id, e.message);
                })
                .listen('.user.typing', (e) => {
                    if (e.user_id !== this.currentUser.id) {
                        this.isOtherTyping = e.is_typing;
                        if (e.is_typing) {
                            clearTimeout(this.typingTimer);
                            this.typingTimer = setTimeout(() => {
                                this.isOtherTyping = false;
                            }, 4000);
                        }
                    }
                });
        },

        // ── Typing indicator ──────────────────────────────────────────────────
        onTyping() {
            if (!this.activeConversation) return;

            clearTimeout(this.typingDebounce);
            window.axios.post(`/conversations/${this.activeConversation.id}/typing`, { is_typing: true }).catch(() => {});

            this.typingDebounce = setTimeout(() => {
                window.axios.post(`/conversations/${this.activeConversation.id}/typing`, { is_typing: false }).catch(() => {});
            }, 2500);
        },

        // ── Read receipts ─────────────────────────────────────────────────────
        markRead(conversationId) {
            window.axios.post(`/conversations/${conversationId}/read`).catch(() => {});
        },

        // ── Conversation preview update ───────────────────────────────────────
        updateConversationPreview(conversationId, message) {
            const idx = this.conversations.findIndex((c) => c.id === conversationId);
            if (idx !== -1) {
                const conv       = this.conversations[idx];
                conv.last_message    = message;
                conv.last_message_at = message.created_at;

                // Increment unread count if it's not the active conversation
                if (this.activeConversation?.id !== conversationId && message.user_id !== this.currentUser.id) {
                    conv.unread_count = (conv.unread_count || 0) + 1;
                }
            } else {
                // Conversation not in list yet – refresh
                this.fetchConversations();
                return;
            }
            this.sortConversations();
        },

        async fetchConversations() {
            try {
                const res        = await window.axios.get('/conversations');
                this.conversations = res.data;
                this.syncOnlineStatus();
            } catch (err) {
                console.error('Failed to refresh conversations', err);
            }
        },

        // ── User search & new conversation ────────────────────────────────────
        async onSearchInput() {
            if (!this.searchQuery.trim()) {
                this.searchResults = [];
                return;
            }
            this.isSearching = true;
            try {
                const res          = await window.axios.get(`/chat/users?q=${encodeURIComponent(this.searchQuery)}`);
                this.searchResults = res.data;
            } catch (err) {
                console.error('User search failed', err);
            } finally {
                this.isSearching = false;
            }
        },

        async startConversationWith(user) {
            try {
                const res          = await window.axios.post('/conversations', { user_id: user.id });
                const conversation = res.data.conversation;

                // Add to list or update existing
                const existing = this.conversations.findIndex((c) => c.id === conversation.id);
                if (existing !== -1) {
                    this.conversations[existing] = conversation;
                } else {
                    this.conversations.unshift(conversation);
                }
                this.syncOnlineStatus();
                this.clearSearch();
                await this.selectConversation(conversation);
            } catch (err) {
                console.error('Failed to start conversation', err);
            }
        },

        closeSearch() {
            this.clearSearch();
        },

        clearSearch() {
            this.searchQuery   = '';
            this.searchResults = [];
            this.isSearchFocused = false;
        },

        // ── File attachment ───────────────────────────────────────────────────
        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            const maxSize = 10 * 1024 * 1024; // 10 MB
            if (file.size > maxSize) {
                alert('File size must not exceed 10 MB.');
                event.target.value = '';
                return;
            }

            this.attachment     = file;
            this.attachmentName = file.name;
            this.attachmentSize = this.formatBytes(file.size);

            if (file.type.startsWith('image/')) {
                this.attachmentType    = 'image';
                const reader           = new FileReader();
                reader.onload          = (e) => { this.attachmentPreview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.attachmentType    = 'file';
                this.attachmentPreview = 'file';
            }
            event.target.value = '';
        },

        clearAttachment() {
            this.attachment        = null;
            this.attachmentPreview = null;
            this.attachmentType    = null;
            this.attachmentName    = null;
            this.attachmentSize    = null;
        },

        // ── Message grouping (date separators) ────────────────────────────────
        rebuildGroups() {
            const groups = [];
            let lastDate = null;

            this.messages.forEach((msg, index) => {
                const date = new Date(msg.created_at).toDateString();
                if (date !== lastDate) {
                    lastDate = date;
                    groups.push({
                        key:   'sep-' + index,
                        type:  'separator',
                        label: this.formatDateSeparator(msg.created_at),
                    });
                }
                groups.push({
                    ...msg,
                    key:     'msg-' + msg.id,
                    type:    'message',   // must come after spread so msg.type doesn't overwrite it
                    type_msg: msg.type,
                });
            });

            this.groupedMessages = groups;
        },

        // ── Infinite scroll (load older messages) ─────────────────────────────
        onMessagesScroll(event) {
            const el = event.target;
            if (el.scrollTop < 80 && this.hasMoreMessages && !this.isLoadingMore) {
                this.loadMessages(true);
            }
        },

        scrollToBottom() {
            const el = document.getElementById('messages-bottom');
            el?.scrollIntoView({ behavior: 'smooth', block: 'end' });
        },

        // ── Textarea auto-resize ──────────────────────────────────────────────
        autoResize(el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        },

        // ── Mobile helpers ────────────────────────────────────────────────────
        isMobile() {
            return window.innerWidth < 768;
        },

        // ── Formatters ────────────────────────────────────────────────────────
        formatConvTime(isoString) {
            if (!isoString) return '';
            const date = new Date(isoString);
            const now  = new Date();
            const diff = now - date;
            const mins = Math.floor(diff / 60000);
            const hrs  = Math.floor(diff / 3600000);

            if (mins < 1)  return 'now';
            if (mins < 60) return `${mins}m`;
            if (hrs  < 24) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            if (hrs  < 48) return 'Yesterday';

            return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
        },

        formatDateSeparator(isoString) {
            const date = new Date(isoString);
            const now  = new Date();
            const diff = Math.floor((now - date) / 86400000);

            if (diff === 0) return 'Today';
            if (diff === 1) return 'Yesterday';
            return date.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        },

        lastMessagePreview(conv) {
            const lm = conv.last_message;
            if (!lm) return 'No messages yet';
            const prefix = lm.user_id === this.currentUser?.id ? 'You: ' : '';
            if (lm.type === 'image') return prefix + '📷 Photo';
            if (lm.type === 'file')  return prefix + '📎 File';
            return prefix + (lm.message || '');
        },

        formatBytes(bytes) {
            if (bytes < 1024)         return bytes + ' B';
            if (bytes < 1024 * 1024)  return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
    };
}
