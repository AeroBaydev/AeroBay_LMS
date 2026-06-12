define([], function() {
    var SELECTORS = {
        unreadRow: '[data-region="trainer-dashboard-unread-row"]',
        unreadBadge: '[data-region="trainer-dashboard-unread-badge"]',
        unreadLabel: '[data-region="trainer-dashboard-unread-label"]'
    };

    var state = {
        unreadUrl: '',
        sesskey: ''
    };

    var updateUnreadBadge = function(count) {
        var unread = Math.max(0, parseInt(count, 10) || 0);
        var row = document.querySelector(SELECTORS.unreadRow);
        var badge = document.querySelector(SELECTORS.unreadBadge);
        var label = document.querySelector(SELECTORS.unreadLabel);

        if (badge) {
            badge.textContent = unread;
        }
        if (label) {
            label.textContent = unread === 1 ? 'unread message' : 'unread messages';
        }
        if (row) {
            row.hidden = unread === 0;
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
                    updateUnreadBadge(data.unread);
                }
            })
            .catch(function() {
                // Keep the last displayed count when a polling request fails.
            });
    };

    return {
        init: function(config) {
            state.unreadUrl = config.unreadurl || '';
            state.sesskey = config.sesskey || '';

            loadUnreadCount();
            window.setInterval(loadUnreadCount, 30000);
        }
    };
});
