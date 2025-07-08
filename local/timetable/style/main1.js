function openRejectPopup(id) {
    // Create the popup HTML
    var popupHtml = `
        <div id="rejectOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
<div id="rejectPopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#f9f9f9; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
    <h2 style="color:#444;">Reject Confirmation</h2>
    <p style="color:#666;">Please provide a reason for rejection:</p>
    <textarea id="rejectReason" rows="4" cols="50" style="width:100%; border-radius:8px; border:1px solid #ccc; padding:8px; resize:vertical;"></textarea><br><br>
    <button style="background:#ff6b6b; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" onclick="submitReject(${id})">Submit</button>
    <button style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" onclick="closePopup()">Cancel</button>
</div>
</div>

    `;

    // Add the popup HTML to the body
    document.body.insertAdjacentHTML('beforeend', popupHtml);
}

function closePopup() {
    var popup = document.getElementById('rejectOverlay');
    if (popup) {
        popup.remove();
    }
}

function submitReject(id) {
var reason = $('#rejectReason').val();

// Create and show a loading indicator
var $loadingIndicator = $('<div id="loadingIndicator" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); color: #fff; display: flex; justify-content: center; align-items: center; font-size: 24px; z-index: 9999;">Loading...</div>');
$('body').append($loadingIndicator);

$.ajax({
    url: 'delete_poc.php',
    type: 'POST',
    data: {
        id: id,
        reason: reason
    },
    success: function(response) {
        try {
            var data = JSON.parse(response);
            if (data.status === 'success') {
                alert('Record rejected successfully!');
                location.reload(); // Refresh the page
            } else {
                alert('An error occurred while rejecting the record.');
            }
        } catch (e) {
            console.error('Failed to parse response:', response);
            alert('An error occurred while processing the request.');
        }
    },
    error: function() {
        alert('An error occurred while processing the request.');
    },
    complete: function() {
        // Remove the loading indicator
        $('#loadingIndicator').remove();
        closePopup(); // Close the popup regardless of success or failure
    }
});
}
