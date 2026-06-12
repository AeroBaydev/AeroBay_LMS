define([], function() {
    var selectors = {
        list: '[data-region="trainer-chat-list"]',
        search: '[data-region="trainer-chat-search"]',
        thread: '[data-region="trainer-chat-thread"]',
        empty: '[data-region="trainer-chat-empty"]',
        panel: '[data-region="trainer-chat-panel"]',
        student: '[data-region="trainer-chat-student"]',
        form: '[data-region="trainer-chat-form"]',
        input: '[data-region="trainer-chat-input"]',
        attachment: '#trainerChatAttachment',
        attachmentName: '[data-region="trainer-chat-attachment-name"]',
        error: '[data-region="trainer-chat-error"]',
        send: '[data-action="trainer-chat-send"]'
    };
    var state = {listUrl: '', threadUrl: '', sendUrl: '', sesskey: '', chatId: 0, maxFileSize: 5242880};
    var find = function(selector) { return document.querySelector(selector); };

    var setError = function(message) {
        var error = find(selectors.error);
        if (error) {
            error.textContent = message || '';
        }
    };

    var validateFile = function(file) {
        if (!file) {
            return '';
        }
        var extension = file.name.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png'].indexOf(extension) === -1) {
            return 'Attachment must be a JPG, JPEG, or PNG image.';
        }
        return file.size > state.maxFileSize ? 'Attachment cannot exceed 5 MB.' : '';
    };

    var scrollThread = function() {
        var thread = find(selectors.thread);
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    };

    var renderMessage = function(message) {
        var thread = find(selectors.thread);
        if (!thread) {
            return;
        }
        var item = document.createElement('div');
        var bubble = document.createElement('div');
        var time = document.createElement('div');
        item.className = 'tc-message' + (message.ismine ? ' mine' : '');
        bubble.className = 'tc-bubble';
        time.className = 'tc-time';
        if (message.attachmenturl) {
            var link = document.createElement('a');
            var image = document.createElement('img');
            link.href = message.attachmenturl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            image.src = message.attachmenturl;
            image.alt = message.attachmentname || 'Chat attachment';
            image.className = 'tc-image';
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
        thread.appendChild(item);
    };

    var loadList = function() {
        var list = find(selectors.list);
        if (!list) {
            return Promise.resolve();
        }
        var search = find(selectors.search);
        var url = state.listUrl + '?sesskey=' + encodeURIComponent(state.sesskey);
        if (search && search.value.trim()) {
            url += '&search=' + encodeURIComponent(search.value.trim());
        }
        list.innerHTML = '<div class="tc-list-status">Loading conversations...</div>';
        return fetch(url, {credentials: 'same-origin'}).then(function(response) {
            return response.json();
        }).then(function(data) {
            if (!data || !data.success) {
                throw new Error((data && data.error) || 'Unable to load conversations.');
            }
            list.textContent = '';
            if (!data.chats.length) {
                list.innerHTML = '<div class="tc-list-status">No student conversations found.</div>';
                return;
            }
            data.chats.forEach(function(chat) {
                var button = document.createElement('button');
                var avatar = document.createElement('span');
                var copy = document.createElement('span');
                var top = document.createElement('span');
                var name = document.createElement('strong');
                var activity = document.createElement('span');
                var bottom = document.createElement('span');
                var preview = document.createElement('span');
                button.type = 'button';
                button.className = 'tc-contact' + (chat.id === state.chatId ? ' active' : '');
                button.dataset.chatId = chat.id;
                avatar.className = 'tc-avatar';
                avatar.textContent = chat.initials;
                copy.className = 'tc-contact-copy';
                top.className = 'tc-contact-top';
                name.textContent = chat.studentname;
                activity.className = 'tc-activity';
                activity.textContent = chat.lastactivity;
                bottom.className = 'tc-contact-bottom';
                preview.className = 'tc-preview';
                preview.textContent = chat.preview;
                top.appendChild(name);
                top.appendChild(activity);
                bottom.appendChild(preview);
                if (chat.unreadcount > 0) {
                    var badge = document.createElement('span');
                    badge.className = 'tc-unread';
                    badge.textContent = chat.unreadcount;
                    bottom.appendChild(badge);
                }
                copy.appendChild(top);
                copy.appendChild(bottom);
                button.appendChild(avatar);
                button.appendChild(copy);
                button.addEventListener('click', function() {
                    openChat(chat.id, chat.studentname);
                });
                list.appendChild(button);
            });
        }).catch(function(error) {
            list.innerHTML = '<div class="tc-list-status tc-list-error"></div>';
            list.firstChild.textContent = error.message || 'Unable to load conversations.';
        });
    };

    var openChat = function(chatId, studentName) {
        state.chatId = parseInt(chatId, 10) || 0;
        var panel = find(selectors.panel);
        var empty = find(selectors.empty);
        var student = find(selectors.student);
        var thread = find(selectors.thread);
        if (panel) {
            panel.hidden = false;
        }
        if (empty) {
            empty.hidden = true;
        }
        if (student) {
            student.textContent = studentName || 'Student';
        }
        if (thread) {
            thread.innerHTML = '<div class="tc-thread-status">Loading conversation...</div>';
        }
        setError('');
        fetch(state.threadUrl + '?sesskey=' + encodeURIComponent(state.sesskey) + '&chatid=' + state.chatId, {
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(data) {
            if (!data || !data.success) {
                throw new Error((data && data.error) || 'Unable to load conversation.');
            }
            thread.textContent = '';
            if (!data.messages.length) {
                thread.innerHTML = '<div class="tc-thread-status">No messages yet.</div>';
            } else {
                data.messages.forEach(renderMessage);
                scrollThread();
            }
            loadList();
        }).catch(function(error) {
            thread.innerHTML = '<div class="tc-thread-status tc-list-error"></div>';
            thread.firstChild.textContent = error.message || 'Unable to load conversation.';
        });
    };

    var sendMessage = function(event) {
        event.preventDefault();
        var form = find(selectors.form);
        var input = find(selectors.input);
        var attachment = find(selectors.attachment);
        var file = attachment && attachment.files.length ? attachment.files[0] : null;
        var message = input ? input.value.trim() : '';
        var fileError = validateFile(file);
        if (fileError) {
            setError(fileError);
            return;
        }
        if (!state.chatId || (!message && !file)) {
            setError('Write a message or attach an image.');
            return;
        }
        var body = new FormData(form);
        body.append('chatid', String(state.chatId));
        body.append('sesskey', state.sesskey);
        var send = find(selectors.send);
        send.disabled = true;
        setError('');
        fetch(state.sendUrl, {method: 'POST', body: body, credentials: 'same-origin'}).then(function(response) {
            return response.json();
        }).then(function(data) {
            send.disabled = false;
            if (!data || !data.success || !data.message) {
                throw new Error((data && data.error) || 'Unable to send message.');
            }
            var status = find(selectors.thread).querySelector('.tc-thread-status');
            if (status) {
                status.remove();
            }
            renderMessage(data.message);
            scrollThread();
            form.reset();
            find(selectors.attachmentName).textContent = '';
            input.focus();
            loadList();
        }).catch(function(error) {
            send.disabled = false;
            setError(error.message || 'Unable to send message.');
        });
    };

    return {
        init: function(config) {
            state.listUrl = config.listurl || '';
            state.threadUrl = config.threadurl || '';
            state.sendUrl = config.sendurl || '';
            state.sesskey = config.sesskey || '';
            var search = find(selectors.search);
            var form = find(selectors.form);
            var attachment = find(selectors.attachment);
            var timer;
            if (search) {
                search.addEventListener('input', function() {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(loadList, 250);
                });
            }
            if (form) {
                form.addEventListener('submit', sendMessage);
            }
            if (attachment) {
                attachment.addEventListener('change', function() {
                    var file = attachment.files.length ? attachment.files[0] : null;
                    var error = validateFile(file);
                    setError(error);
                    find(selectors.attachmentName).textContent = file && !error ? file.name : '';
                });
            }
            loadList();
        }
    };
});
