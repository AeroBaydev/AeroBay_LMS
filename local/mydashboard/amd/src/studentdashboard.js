define([], function() {
    var SELECTORS = {
        open: '[data-action="open-student-chat"]',
        close: '[data-action="close-student-chat"]',
        drawer: '[data-region="student-chat-drawer"]',
        trainer: '[data-region="student-chat-trainer"]',
        status: '[data-region="student-chat-status"]',
        statusDot: '[data-region="student-chat-status-dot"]',
        messages: '[data-region="student-chat-messages"]',
        form: '[data-region="student-chat-form"]',
        input: '[data-region="student-chat-input"]',
        attachment: '#abStudentChatAttachment',
        attachmentName: '[data-region="student-chat-attachment-name"]',
        send: '[data-action="send-student-chat"]',
        error: '[data-region="student-chat-error"]',
        cardUnread: '[data-region="trainer-unread-card"]',
        navbarMessages: '[data-region="popover-region-messages"] .popover-region-toggle',
        navbarNativeUnread: '[data-region="popover-region-messages"] [data-region="count-container"]',
        navbarUnread: '[data-region="trainer-unread-navbar"]'
    };

    var state = {
        studentUrl: '',
        threadUrl: '',
        sendUrl: '',
        unreadUrl: '',
        sesskey: '',
        chatId: 0,
        openRequestId: 0,
        maxFileSize: 5 * 1024 * 1024
    };

    var getElement = function(selector) {
        return document.querySelector(selector);
    };

    var setError = function(message) {
        var error = getElement(SELECTORS.error);
        if (error) {
            error.textContent = message || '';
        }
    };

    var setMessagesStatus = function(message) {
        var container = getElement(SELECTORS.messages);
        if (!container) {
            return;
        }
        container.textContent = '';
        var empty = document.createElement('div');
        empty.className = 'ab-chat-empty';
        empty.textContent = message;
        container.appendChild(empty);
    };

    var getNavbarUnread = function() {
        var badge = getElement(SELECTORS.navbarUnread);
        var navbarMessages = getElement(SELECTORS.navbarMessages);
        if (!badge && navbarMessages) {
            badge = document.createElement('span');
            badge.className = 'aero-unread-badge aero-unread-badge-navbar';
            badge.dataset.region = 'trainer-unread-navbar';
            badge.hidden = true;
            navbarMessages.appendChild(badge);
        }
        return badge;
    };

    var updateUnreadBadges = function(count) {
        var unread = Math.max(0, parseInt(count, 10) || 0);
        var cardBadge = getElement(SELECTORS.cardUnread);
        var navbarBadge = getNavbarUnread();
        var nativeNavbarBadge = getElement(SELECTORS.navbarNativeUnread);
        var nativeNavbarCount = 0;

        if (nativeNavbarBadge) {
            var nativeCountText = nativeNavbarBadge.querySelector('span[aria-hidden="true"]');
            nativeNavbarCount = Math.max(0, parseInt(nativeCountText ? nativeCountText.textContent : '', 10) || 0);
            nativeNavbarBadge.classList.toggle('hidden', unread > 0 || nativeNavbarCount === 0);
        }

        if (cardBadge) {
            cardBadge.textContent = unread + (unread === 1 ? ' unread message' : ' unread messages');
            cardBadge.hidden = unread === 0;
        }
        if (navbarBadge) {
            var combinedUnread = nativeNavbarCount + unread;
            navbarBadge.textContent = combinedUnread > 99 ? '99+' : combinedUnread;
            navbarBadge.setAttribute('aria-label', combinedUnread + ' unread messages');
            navbarBadge.hidden = unread === 0;
        }
    };

    var loadUnreadCount = function() {
        if (!state.unreadUrl) {
            return Promise.resolve();
        }
        var url = state.unreadUrl + '?sesskey=' + encodeURIComponent(state.sesskey);
        return fetch(url, {credentials: 'same-origin'})
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data && data.success) {
                    updateUnreadBadges(data.unread);
                }
            })
            .catch(function() {
                // Keep the last displayed count when a polling request fails.
            });
    };

    var appendMessage = function(message) {
        var container = getElement(SELECTORS.messages);
        if (!container) {
            return;
        }
        var empty = container.querySelector('.ab-chat-empty');
        if (empty) {
            empty.remove();
        }

        var item = document.createElement('div');
        var bubble = document.createElement('div');
        var time = document.createElement('div');
        item.className = 'ab-chat-message' + (message.ismine ? ' mine' : '');
        bubble.className = 'ab-chat-bubble';
        time.className = 'ab-chat-time';

        if (message.attachmenturl) {
            var link = document.createElement('a');
            var image = document.createElement('img');
            link.href = message.attachmenturl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            image.className = 'ab-chat-image';
            image.src = message.attachmenturl;
            image.alt = message.attachmentname || 'Chat attachment';
            link.appendChild(image);
            bubble.appendChild(link);
        }
        if (message.message) {
            var text = document.createElement('div');
            text.innerHTML = message.message;
            bubble.appendChild(text);
        }
        time.textContent = message.timestamp || '';
        item.appendChild(bubble);
        item.appendChild(time);
        container.appendChild(item);
    };

    var scrollToLatest = function() {
        var container = getElement(SELECTORS.messages);
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    };

    var updateHeader = function(chat) {
        var trainer = getElement(SELECTORS.trainer);
        var status = getElement(SELECTORS.status);
        var statusDot = getElement(SELECTORS.statusDot);
        if (trainer) {
            trainer.textContent = chat.trainername || 'Trainer';
        }
        if (status) {
            status.textContent = chat.status || 'Offline';
        }
        if (statusDot) {
            statusDot.classList.toggle('online', Boolean(chat.isonline));
        }
    };

    var loadThread = function(chatId) {
        if (!chatId || chatId !== state.chatId) {
            return Promise.resolve();
        }
        var url = state.threadUrl + '?sesskey=' + encodeURIComponent(state.sesskey)
            + '&chatid=' + encodeURIComponent(chatId);
        return fetch(url, {credentials: 'same-origin'})
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || !data.success) {
                    throw new Error((data && data.error) || 'Unable to load chat history.');
                }
                var container = getElement(SELECTORS.messages);
                if (container) {
                    container.textContent = '';
                }
                updateUnreadBadges(0);
                if (!data.messages.length) {
                    setMessagesStatus('No messages yet. Start the conversation.');
                    return;
                }
                data.messages.forEach(appendMessage);
                scrollToLatest();
            });
    };

    var openChat = function() {
        var drawer = getElement(SELECTORS.drawer);
        if (!drawer) {
            return;
        }
        state.chatId = 0;
        state.openRequestId++;
        var openRequestId = state.openRequestId;
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setError('');
        setMessagesStatus('Loading conversation...');

        fetch(state.studentUrl + '?sesskey=' + encodeURIComponent(state.sesskey), {credentials: 'same-origin'})
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || !data.success || !data.chat) {
                    throw new Error((data && data.error) || 'Unable to open chat.');
                }
                if (openRequestId !== state.openRequestId) {
                    return;
                }
                var chatId = parseInt(data.chat.id, 10) || 0;
                if (!chatId) {
                    throw new Error('Unable to open chat.');
                }
                state.chatId = chatId;
                updateHeader(data.chat);
                return loadThread(chatId);
            })
            .catch(function(error) {
                setMessagesStatus(error.message || 'Unable to open chat.');
            });
    };

    var closeChat = function() {
        var drawer = getElement(SELECTORS.drawer);
        if (!drawer) {
            return;
        }
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    var validateAttachment = function(file) {
        if (!file) {
            return '';
        }
        var extension = file.name.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png'].indexOf(extension) === -1) {
            return 'Attachment must be a JPG, JPEG, or PNG image.';
        }
        if (file.size > state.maxFileSize) {
            return 'Attachment cannot exceed 5 MB.';
        }
        return '';
    };

    var setSending = function(sending) {
        var send = getElement(SELECTORS.send);
        if (send) {
            send.disabled = sending;
        }
    };

    var sendMessage = function(event) {
        event.preventDefault();
        var form = getElement(SELECTORS.form);
        var input = getElement(SELECTORS.input);
        var attachment = getElement(SELECTORS.attachment);
        var file = attachment && attachment.files.length ? attachment.files[0] : null;
        var message = input ? input.value.trim() : '';
        var attachmentError = validateAttachment(file);
        var chatId = state.chatId;

        if (attachmentError) {
            setError(attachmentError);
            return;
        }
        if (!chatId || (message === '' && !file)) {
            setError('Write a message or attach an image.');
            return;
        }

        var body = new FormData(form);
        body.append('chatid', String(chatId));
        body.append('sesskey', state.sesskey);
        setError('');
        setSending(true);

        fetch(state.sendUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            setSending(false);
            if (!data || !data.success || !data.message) {
                setError((data && data.error) || 'Unable to send message.');
                return;
            }
            appendMessage(data.message);
            scrollToLatest();
            form.reset();
            var attachmentName = getElement(SELECTORS.attachmentName);
            if (attachmentName) {
                attachmentName.textContent = '';
            }
            if (input) {
                input.focus();
            }
        })
        .catch(function() {
            setSending(false);
            setError('Network error. Please try again.');
        });
    };

    var bindEvents = function() {
        var open = getElement(SELECTORS.open);
        var drawer = getElement(SELECTORS.drawer);
        var form = getElement(SELECTORS.form);
        var attachment = getElement(SELECTORS.attachment);
        var input = getElement(SELECTORS.input);

        if (open) {
            open.addEventListener('click', openChat);
        }
        document.querySelectorAll(SELECTORS.close).forEach(function(button) {
            button.addEventListener('click', closeChat);
        });
        if (drawer) {
            drawer.addEventListener('click', function(event) {
                if (event.target === drawer) {
                    closeChat();
                }
            });
        }
        if (form) {
            form.addEventListener('submit', sendMessage);
        }
        if (attachment) {
            attachment.addEventListener('change', function() {
                var attachmentName = getElement(SELECTORS.attachmentName);
                var file = attachment.files.length ? attachment.files[0] : null;
                var error = validateAttachment(file);
                setError(error);
                if (attachmentName) {
                    attachmentName.textContent = file && !error ? file.name : '';
                }
            });
        }
        if (input) {
            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    form.requestSubmit();
                }
            });
        }
    };

    return {
        init: function(config) {
            state.studentUrl = config.studenturl || '';
            state.threadUrl = config.threadurl || '';
            state.sendUrl = config.sendurl || '';
            state.unreadUrl = config.unreadurl
                || state.studentUrl.replace(/ajax_chat_student\.php.*$/, 'ajax_chat_unread.php');
            state.sesskey = config.sesskey || '';
            bindEvents();
            loadUnreadCount();
            window.setInterval(loadUnreadCount, 30000);
        }
    };
});
