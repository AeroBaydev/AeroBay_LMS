document.addEventListener('DOMContentLoaded', function() {
    const moveRightButton = document.getElementById('move-right');
    const moveLeftButton = document.getElementById('move-left');
    const leftList = document.getElementById('left-list');
    const rightList = document.getElementById('right-list');
    const leftSearch = document.getElementById('left-search');
    const rightSearch = document.getElementById('right-search');
    const userId = document.getElementById('userId');
    const assignedcount = document.getElementById('assigned-count');
    const availablecount = document.getElementById('available-count');

    updateCounts();
    
    function moveSelectedData(sourceContainer, targetContainer, action) {
        const checkboxes = sourceContainer.querySelectorAll('input[type="checkbox"]:checked');
        const movedData = [];
        checkboxes.forEach(checkbox => {
            const label = checkbox.parentElement;
            movedData.push(checkbox.value); 
            checkbox.checked = false;
            targetContainer.appendChild(label);
        });
        updateCounts();
        if (movedData.length > 0) {
            sendAjaxRequest(action, movedData);
        }
    }

    function sendAjaxRequest(action, data) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', M.cfg.wwwroot + '/local/poc/get_school_data1.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status !== 'success') {
                    alert('An error occurred while updating the data.');
                }
            }
        };
        xhr.send('action=' + action + '&userId=' + userId.value + '&data=' + JSON.stringify(data));
    }

    function filterList(searchInput, list) {
        const filter = searchInput.value.toLowerCase();
        const labels = list.getElementsByTagName('label');
        for (let i = 0; i < labels.length; i++) {
            const label = labels[i];
            const text = label.textContent || label.innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                label.style.display = "";
            } else {
                label.style.display = "none";
            }
        }
    }

    function updateCounts() {
        assignedcount.textContent = leftList.getElementsByTagName('label').length;
        availablecount.textContent = rightList.getElementsByTagName('label').length;
    }
    moveRightButton.addEventListener('click', function() {
        moveSelectedData(rightList, leftList, 'assign');
        updateButtonState();
    });

    moveLeftButton.addEventListener('click', function() {
        moveSelectedData(leftList, rightList, 'remove');
    });

    leftSearch.addEventListener('input', function() {
        filterList(leftSearch, leftList);
    });

    rightSearch.addEventListener('input', function() {
        filterList(rightSearch, rightList);
    });


});
