<?php
require_once('../../config.php');
require_once('classes/form/assign_arm.php');

global $PAGE, $CFG, $DB, $OUTPUT,$USER;
// $PAGE->requires->js(new moodle_url('amd/src/pocmanage.js')); // Ensure this file exists and is correctly linked
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title("Assign ARM's to RM");
// $PAGE->navbar->add("Assign ARM's to RM", new moodle_url('/assign_arm_to_rm.php'));
$PAGE->set_heading("Assign ARM's to RM");

// $rmid = $_POST['rmid'];   
$rmid = optional_param('rmid', 0, PARAM_INT);
$PAGE->navbar->add('RM/ARM Management', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?roleid=12");
$PAGE->navbar->add("Assign ARM's to RM", "$CFG->wwwroot/local/regionalpoc/assign_arm_to_rm.php?rmid=$rmid");

// var_dump($rmid);
// die;

$mform = new assign_arm();

echo $OUTPUT->header();
// echo "<a href=\"{$CFG->wwwroot}/local/regionalpoc/rm_arm_manage.php?roleid=3\" class='btn btn-primary'>BACK</a>";
echo $OUTPUT->heading($PAGE->heading);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // var_dump($_POST);
        // die;
        // Handle add users
        if (isset($_POST['addselect'])) {
            $selected_users = $_POST['addselect'];
            foreach ($selected_users as $armid) {
                // Add user to the assigned_arm table
                $DB->execute("INSERT INTO {assigned_arm} (rmid, armid) VALUES (?, ?)", [$rmid, $armid]);
                
                // Update regionalpoc table
                $DB->execute("UPDATE {regionalpoc} SET status = 1 WHERE id = ? AND role = 'asstmanager'", [$armid]);
            }
        }
    } elseif (isset($_POST['remove'])) {
        // Handle remove users
        if (isset($_POST['removeselect'])) {
            $selected_users = $_POST['removeselect'];
            foreach ($selected_users as $armid) {
                // var_dump($armid);
                // var_dump($rmid);
                // die('hiioi');
                // Remove user from the assigned_arm table
                $DB->execute("DELETE FROM {assigned_arm} WHERE rmid = ? AND armid = ?", [$rmid, $armid]);
                $DB->execute("UPDATE {regionalpoc} SET status = 0 WHERE id = ? AND role = 'asstmanager'", [$armid]);
            }
        }
    }
}

if ($mform->is_cancelled()) {
    // Handle form cancel
} else if ($fromform = $mform->get_data()) {
    $mform->set_data($fromform);
}

// Fetch users from the database
$users = $DB->get_records_sql("SELECT id, username FROM {regionalpoc} WHERE role='asstmanager' AND status=0 AND pocid = $USER->id");
$existing_users = $DB->get_records_sql("SELECT aa.armid AS id, arp.username FROM {assigned_arm} aa JOIN {regionalpoc} arp ON aa.armid = arp.id WHERE arp.status = 1");

$mform->display();
?>
<style>
    a.backbutton.btn.btn-primary {
    margin-left: 53px;
    }

</style>
<form method="post" action="">
    <input type="hidden" name="rmid" value="<?php  echo $rmid; ?>" id="rmid">
  
    <table id="assigningrole" summary="" class="admintable roleassigntable generaltable" cellspacing="0">
        <tbody>
            <tr>
                <td id="existingcell">
                    <p><label for="removeselect">Existing users</label></p>
                    <div class="userselector" id="removeselect_wrapper">
                        <select name="removeselect[]" id="removeselect" multiple="multiple" size="20" class="form-control no-overflow">
                            <!-- Options will be populated via JavaScript -->
                        </select>
                        <div class="form-inline">
                            <label for="removeselect_searchtext">Search</label>
                            <input type="text" name="removeselect_searchtext" id="removeselect_searchtext" size="15" value="" class="form-control">
                            <input type="button" value="Clear" class="btn btn-secondary mx-1" id="removeselect_clearbutton" onclick="clearSearch('removeselect_searchtext')">
                        </div>
                    </div>
                </td>
                <td id="buttonscell">
                    <div id="addcontrols">
                        <input name="add" id="add" type="submit" value="◄&nbsp;Add" title="Add" class="btn btn-secondary" disabled><br>
                    </div>
                    <div id="removecontrols">
                        <input name="remove" id="remove" type="submit" value="Remove&nbsp;►" title="Remove" class="btn btn-secondary" disabled><br>
                    </div>
                    
                </td>
                <td id="potentialcell">
                    <p><label for="addselect">Potential users</label></p>
                    <div class="userselector" id="addselect_wrapper">
                        <select name="addselect[]" id="addselect" multiple="multiple" size="20" class="form-control no-overflow">
                            <optgroup label="Potential users">
                                <?php foreach ($users as $user) { ?>
                                    <option value="<?= $user->id; ?>"><?= $user->username; ?></option>
                                <?php } ?>
                            </optgroup>
                        </select>
                        <div class="form-inline">
                            <label for="addselect_searchtext">Search</label>
                            <input type="text" name="addselect_searchtext" id="addselect_searchtext" size="15" value="" class="form-control">
                            <input type="button" value="Clear" class="btn btn-secondary mx-1" id="addselect_clearbutton" onclick="clearSearch('addselect_searchtext')">
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</form>

<?php
echo $OUTPUT->footer();
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.onbeforeunload=null
    const addSelect = document.getElementById('addselect');
    const removeSelect = document.getElementById('removeselect');
    const addBtn = document.getElementById('add');
    const removeBtn = document.getElementById('remove');
    const roleSelect = document.getElementById('id_role');
    // var rmid = document.getElementById('rmid');
    const rmid= new URLSearchParams(window.location.search).get('rmid');
    // console.log(rmid)
    fetchExistingUsers(rmid);

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


    document.getElementById('addselect_searchtext').addEventListener('input', () => {
    //     const selectedValue = this.value;
        filterOptions('addselect', 'addselect_searchtext');
    //     if (selectedValue != 0) {
    //         // console.log(selectedValue)
            
    //     }
    // });
    });
    document.getElementById('removeselect_searchtext').addEventListener('input', () => {
        filterOptions('removeselect', 'removeselect_searchtext');
    });

    // roleSelect.addEventListener('change', function () {
    //     const selectedValue = this.value;
    //     if (selectedValue != 0) {
    //         // console.log(selectedValue)
            
    //     }
    // });

    function clearSearch(inputId) {
        document.getElementById(inputId).value = '';
        filterOptions(inputId.replace('_searchtext', ''), inputId);
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

    function filterOptions(selectId, inputId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const select = document.getElementById(selectId);
    const options = select.getElementsByTagName('option');
    for (let i = 0; i < options.length; i++) {
        const txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toLowerCase().indexOf(filter) > -1) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }
    }

   
});
</script>

