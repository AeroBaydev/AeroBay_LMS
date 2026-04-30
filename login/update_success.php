<?php
require('../config.php');

$PAGE->set_url('/login/update_success.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('updatesuccess', 'local_students'));
$PAGE->set_heading($SITE->fullname);
$PAGE->requires->css(new moodle_url("$CFG->wwwroot/login/style/custom.css"));

// echo $OUTPUT->header();
?>
<div id="success-popup" class="modal">
    <div class="modal-content">
        <p><?php echo get_string('updatesuccessmessage', 'local_students'); ?></p>
        <button id="ok-button"><?php echo get_string('ok', 'local_students'); ?></button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('success-popup');
    var okButton = document.getElementById('ok-button');
    
    modal.style.display = 'block';
    
    okButton.onclick = function() {
        window.location.href = '<?php echo $CFG->wwwroot; ?>/login/';
    };
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            window.location.href = '<?php echo $CFG->wwwroot; ?>/login/';
        }
    };
});
</script>
<style>
.modal {
    display: block;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 30%;
    text-align: center;
    border-radius: 10px;
}
button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 5px;
}
button:hover {
    background-color: #45a049;
}
</style>
<?php
// echo $OUTPUT->footer();
?>
