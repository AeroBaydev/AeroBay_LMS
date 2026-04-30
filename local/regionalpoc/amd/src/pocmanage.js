document.addEventListener('DOMContentLoaded', function () {
    window.onbeforeunload=null
    const addSelect = document.getElementById('addselect');
    const removeSelect = document.getElementById('removeselect');
    const addBtn = document.getElementById('add');
    const removeBtn = document.getElementById('remove');
    const roleSelect = document.getElementById('id_role');
    var rmid = document.getElementById('rmid');

    addBtn.addEventListener('click',()=>{
        rmid.setAttribute('value',roleSelect.value);
    })
    removeBtn.addEventListener('click',()=>{
        rmid.setAttribute('value',roleSelect.value);
    })

    addSelect.addEventListener('change', () => {
        addBtn.disabled = addSelect.selectedOptions.length === 0;
    });

    removeSelect.addEventListener('change', () => {
        removeBtn.disabled = removeSelect.selectedOptions.length === 0;
    });

    roleSelect.addEventListener('change', function () {
        const selectedValue = this.value;
        if (selectedValue != 0) {
            console.log(selectedValue)
            fetchExistingUsers(selectedValue);
        }
    });

    function clearSearch(inputId) {
        document.getElementById(inputId).value = '';
    }

    function fetchExistingUsers(roleId) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_existing_users.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                const users = JSON.parse(xhr.responseText);
                updateRemoveSelect(users);
            }
        };
        xhr.send('roleId=' + roleId);
    }

    function updateRemoveSelect(users) {
        removeSelect.innerHTML = '';
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.username;
            removeSelect.appendChild(option);
        });
    }
});