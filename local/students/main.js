
function openRejectPopup(id) {
    // Create the popup HTML
    var popupHtml = `
        <div id="rejectOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
<div id="rejectPopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#f9f9f9; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
    <h2 style="color:#444;">Reject Confirmation</h2>
    <p style="color:#666;">Please provide a reason for rejection:</p>
    <textarea id="rejectReason" rows="4" cols="50" style="width:100%; border-radius:8px; border:1px solid #ccc; padding:8px; resize:vertical;"></textarea><br><br>
    <button style="background:#ff6b6b; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class ="btn btn-primary" onclick="submitReject(${id})">Submit</button>
    <button style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class ="btn btn-primary" onclick="closeRejectPopup()">Cancel</button>
</div>
</div>

    `;

    // Add the popup HTML to the body
    document.body.insertAdjacentHTML('beforeend', popupHtml);
}

function closeRejectPopup() {
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
    url: 'reject_poc.php',
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



 // deleted

function openDeletePopup(id,i) {
    var popupHtml = `
    <div id="deleteOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div id="deletePopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#f9f9f9; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
            <h2 style="color:#444;" >Confirm Deletion</h2>
            <p style="color:#666;">Are you sure you want to delete this student?</p>
            <button style="background:#ff6b6b; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class ="btn btn-primary" onclick="confirmDelete(${id},${i})">Confirm</button>
            <button style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;" class ="btn btn-primary" onclick="closePopup()">Cancel</button>
        </div>
    </div>`;

    // Add the popup to the body
    document.body.insertAdjacentHTML('beforeend', popupHtml);
}

function closePopup() {
    // Remove the popup from the DOM
    $('#deleteOverlay').remove();
}

function confirmDelete(id,i) {
    // Close the popup
    closePopup();

    // Create and show a loading indicator
    var $loadingIndicator = $('<div id="loadingIndicator" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); color: #fff; display: flex; justify-content: center; align-items: center; font-size: 24px; z-index: 9999;">Loading...</div>');
    $('body').append($loadingIndicator);

    $.ajax({
        url: 'delete_student2.php',
        type: 'POST',
        data: {
            id: id
        },
        success: function(response) {
            try {
                var data = JSON.parse(response);
                var statusMessage = data.status === 'success' ? 'Record deleted successfully!' : 'An error occurred while deleting the record.';
                showStatusPopup('Success', statusMessage);
            } catch (e) {
                console.error('Failed to parse response:', response);
                showStatusPopup('Error', 'An error occurred while processing the request.');
            }
        },
        error: function() {
            showStatusPopup('Error', 'An error occurred while processing the request.');
        },
        complete: function() {
            // Remove the loading indicator
            $('#uniqueid_r'+i).remove();
            $('#loadingIndicator').remove();
        }
    });
}

function showStatusPopup(title, message) {
    var statusPopupHtml = `
    <div id="statusOverlay" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div id="statusPopup" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border-radius:12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width:300px; max-width:90%; z-index:1001;">
            <h2 style="color:#444;" id="statusTitle">${title}</h2>
            <p style="color:#666;" id="statusMessage">${message}</p>
            <button onclick="closeStatusPopup()" class ="btn btn-primary" style="background:#ccc; color:white; border:none; border-radius:8px; padding:10px 15px; cursor:pointer;">Close</button>
        </div>
    </div>`;
    
    // Add the status popup to the body
    document.body.insertAdjacentHTML('beforeend', statusPopupHtml);
}

function closeStatusPopup() {
    $('#statusOverlay').remove();
}
///checkbox code
document.addEventListener('DOMContentLoaded', function () {
    // Select/Deselect all checkboxes
    document.getElementById('select-all').addEventListener('click', function (event) {
        var isChecked = this.checked;
        document.querySelectorAll('.student-select').forEach(function (checkbox) {
            checkbox.checked = isChecked;
        });
    });

    // Handle the batch approve button click event
    document.getElementById('batch-approve-btn').addEventListener('click', function () {
        // Collect all selected student IDs
        var selectedStudents = [];
        document.querySelectorAll('.student-select:checked').forEach(function (checkbox) {
            selectedStudents.push(checkbox.value);
        });

        if (selectedStudents.length > 0) {
            // Send AJAX request to approve.php
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'approved.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    alert('Selected students approved successfully!');
                    location.reload(); // Refresh the page to reflect changes
                }
            };
            xhr.send('ids=' + JSON.stringify(selectedStudents));
        } else {
            alert('Please select at least one student to approve.');
        }
    });
});
///checkbox code end