
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listener for the toggle buttons
    document.querySelectorAll('.toggle-action').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default action

            var pocId = this.getAttribute('data-id');
            var action = this.getAttribute('data-action');
            var toggleButton = this;

            // Show a confirmation dialog
            if (confirm('Are you sure you want to ' + action + ' this POC?')) {
                // Perform the AJAX request
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ajax_toggle_poc.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            // Parse the JSON response
                            var response = JSON.parse(xhr.responseText);

                            if (response.success) {
                                // Update button based on new status
                                if (action === 'suspend') {
                                    toggleButton.setAttribute('data-action', 'activate');
                                    toggleButton.setAttribute('title', 'Activate POC');
                                    toggleButton.querySelector('i').className = 'fa fa-eye-slash fa-fw';
                                } else {
                                    toggleButton.setAttribute('data-action', 'suspend');
                                    toggleButton.setAttribute('title', 'Suspend POC');
                                    toggleButton.querySelector('i').className = 'fa fa-eye fa-fw';
                                }
                            } else {
                                // Handle error
                                alert('Error: Unable to ' + action + ' POC.');
                            }
                        } else {
                            // Handle error
                            alert('Error: Unable to ' + action + ' POC.');
                        }
                    }
                };
                xhr.send('id=' + pocId + '&action=' + action);
            }
        });
    });
});

