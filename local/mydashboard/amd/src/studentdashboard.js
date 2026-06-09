define(['core/notification'], function(Notification) {
    var SELECTORS = {
        openButton: '[data-action="open-ask-doubt"]',
        closeButton: '[data-action="close-ask-doubt"]',
        modal: '[data-region="ask-doubt-modal"]',
        form: '[data-region="ask-doubt-form"]',
        question: '#abAskDoubtQuestion',
        error: '[data-region="ask-doubt-error"]',
        count: '[data-region="ask-doubt-count"]',
        submit: '[data-action="submit-ask-doubt"]'
    };

    var state = {
        submitUrl: '',
        maxChars: 1000
    };

    var getElement = function(selector) {
        return document.querySelector(selector);
    };

    var setError = function(message) {
        var error = getElement(SELECTORS.error);
        var question = getElement(SELECTORS.question);
        if (error) {
            error.textContent = message || '';
        }
        if (question) {
            question.classList.toggle('is-invalid', Boolean(message));
        }
    };

    var updateCounter = function() {
        var question = getElement(SELECTORS.question);
        var count = getElement(SELECTORS.count);
        if (!question || !count) {
            return;
        }
        count.textContent = String(question.value.length);
    };

    var openModal = function() {
        var modal = getElement(SELECTORS.modal);
        if (!modal) {
            return;
        }
        setError('');
        updateCounter();
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var question = getElement(SELECTORS.question);
        if (question) {
            question.focus();
        }
    };

    var closeModal = function() {
        var modal = getElement(SELECTORS.modal);
        if (!modal) {
            return;
        }
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    var resetForm = function() {
        var form = getElement(SELECTORS.form);
        if (form) {
            form.reset();
        }
        setError('');
        updateCounter();
    };

    var setLoading = function(loading) {
        var submit = getElement(SELECTORS.submit);
        if (!submit) {
            return;
        }
        submit.disabled = loading;
        submit.textContent = loading ? 'Sending...' : 'Send';
    };

    var validate = function() {
        var question = getElement(SELECTORS.question);
        var text = question ? question.value.trim() : '';
        if (text === '') {
            setError('Please enter your doubt or question.');
            return false;
        }
        if (text.length > state.maxChars) {
            setError('Doubt text cannot exceed ' + state.maxChars + ' characters.');
            return false;
        }
        setError('');
        return true;
    };

    var submitDoubt = function(event) {
        event.preventDefault();
        if (!validate()) {
            return;
        }

        var form = getElement(SELECTORS.form);
        if (!form) {
            return;
        }

        setLoading(true);
        fetch(state.submitUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            setLoading(false);
            if (data && data.success) {
                closeModal();
                resetForm();
                Notification.addNotification({
                    message: data.message || 'Your doubt has been sent to your trainer.',
                    type: 'success'
                });
                return;
            }
            setError((data && data.error) || 'Unable to send your doubt. Please try again.');
        })
        .catch(function() {
            setLoading(false);
            setError('Network error. Please try again.');
        });
    };

    var bindEvents = function() {
        var openButton = getElement(SELECTORS.openButton);
        var form = getElement(SELECTORS.form);
        var question = getElement(SELECTORS.question);
        var modal = getElement(SELECTORS.modal);
        var closeButtons = document.querySelectorAll(SELECTORS.closeButton);

        if (openButton) {
            openButton.addEventListener('click', openModal);
        }
        closeButtons.forEach(function(button) {
            button.addEventListener('click', closeModal);
        });
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }
        if (question) {
            question.addEventListener('input', function() {
                updateCounter();
                if (question.value.trim() !== '') {
                    setError('');
                }
            });
        }
        if (form) {
            form.addEventListener('submit', submitDoubt);
        }
    };

    return {
        init: function(config) {
            state.submitUrl = config.submiturl || '';
            state.maxChars = parseInt(config.maxchars, 10) || 1000;
            bindEvents();
            updateCounter();
        }
    };
});
