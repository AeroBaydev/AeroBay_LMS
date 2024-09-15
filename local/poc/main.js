
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listener for the toggle buttons
    document.querySelectorAll('.toggle-action').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default action

            var pocId = this.getAttribute('data-id');
            var action = this.getAttribute('data-action');
            
            // Open the confirmation popup
            openSuspendPopup(pocId, action, this);
        });
    });
});

function openSuspendPopup(id, action, toggleButton) {
    var popupHtml = `
    <div id="deleteOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div id="deletePopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#f9f9f9; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
            <h2 style="color:#444;">Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}</h2>
            <p style="color:#666;">Are you sure you want to ${action} this POC?</p>
            <button style="background:#ff6b6b; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class="btn btn-primary" onclick="confirmAction(${id}, '${action}', this)">Confirm</button>
            <button style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class="btn btn-primary" onclick="closePopup()">Cancel</button>
        </div>
    </div>`;

    // Add the popup to the body
    document.body.insertAdjacentHTML('beforeend', popupHtml);
}

function closePopup() {
    // Remove the popup from the DOM
    document.getElementById('deleteOverlay').remove();
}

function confirmAction(id, action, toggleButton) {
    // Close the popup
    closePopup();
   // var action = this.getAttribute('data-action');
    var toggleButton = this;
    // Create and show a loading indicator
    var loadingIndicator = document.createElement('div');
    loadingIndicator.id = 'loadingIndicator';
    loadingIndicator.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); color: #fff; display: flex; justify-content: center; align-items: center; font-size: 24px; z-index: 9999;';
    loadingIndicator.textContent = 'Loading...';
    document.body.appendChild(loadingIndicator);

    // Perform the AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_toggle_poc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
               
                    if (data.success) {
                        //showStatusPopup('Success', statusMessage);
                     // alert(action);
                        updateToggleButton(action,id);
                    }

                    // console.log('Parsed Data:', data); 
                    // var statusMessage = data.success ? `POC ${action}  successfully!` : 'An error occurred while processing the request.';
                    // showStatusPopup('Success', statusMessage);

                    // // Update the button based on the action performed
                    // updateToggleButton(toggleButton, action);
                } catch (e) {
                    console.error('Failed to parse response:', xhr.responseText);
                    showStatusPopup('Error', 'An error occurred while processing the request.');
                }
            } else {
                //console.log(xhr.responseText);
                showStatusPopup('Error', 'An error occurred while processing the request.');
            }
            // Remove the loading indicator
            document.getElementById('loadingIndicator').remove();
        }
    };
    xhr.send('id=' + id + '&action=' + action);
}

function updateToggleButton(action,id) {
    var element = document.getElementById('poc_'+id);
    var pocIcon = document.getElementById('poc_icon_'+id);
    
    if (action === 'suspend') {
        pocIcon.className ='fa fa-eye-slash fa-fw';
        
        // Update title attribute
        element.setAttribute('title', 'Activate POC');
        
        // Update data-action attribute
        element.setAttribute('data-action', 'activate'); // Change to eye icon for activate
    } else {

        
         pocIcon.className = 'fa fa-eye fa-fw';
        // Update title attribute
        element.setAttribute('data-action', 'suspend');;
        
        // Update data-action attribute
        element.setAttribute('title', 'Suspend POC');
        // toggleButton.setAttribute('data-action', 'suspend');
        // toggleButton.setAttribute('title', 'Suspend POC');
        // toggleButton.querySelector('i').className = 'fa fa-eye-slash fa-fw';  // Change to eye-slash icon for suspend
    }
}

function showStatusPopup(title, message) {
    var statusPopupHtml = `
    <div id="statusOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div id="statusPopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
            <h2 style="color:#444;" id="statusTitle">${title}</h2>
            <p style="color:#666;" id="statusMessage">${message}</p>
            <button onclick="closeStatusPopup()" class="btn btn-primary" style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;">Close</button>
        </div>
    </div>`;

    // Add the status popup to the body
    document.body.insertAdjacentHTML('beforeend', statusPopupHtml);
}

function closeStatusPopup() {
    document.getElementById('statusOverlay').remove();
}

