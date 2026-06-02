<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER;

$action = required_param('action', PARAM_ALPHA);
$data = json_decode(required_param('data', PARAM_RAW));
$userId = required_param('userId', PARAM_INT);

$success = false;
$error = '';
$pocuserid = $USER->id;
if (is_siteadmin() && !empty($_SESSION['userIdPoc'])) {
    $pocuserid = (int) $_SESSION['userIdPoc'];
}

$trainer = $DB->get_record('trainer', ['userid' => $userId], '*', MUST_EXIST);
$canmanage = is_siteadmin();
if (!$canmanage) {
    if (!empty($trainer->schoolid)) {
        if ($DB->record_exists('course_categories', ['id' => $trainer->schoolid])) {
            $canmanage = $DB->record_exists('schoolassign', [
                'userid' => $pocuserid,
                'schoolid' => $trainer->schoolid,
            ]);
        } else {
            $canmanage = ((int) $trainer->createdby === (int) $pocuserid);
        }
    } else {
        $canmanage = ((int) $trainer->createdby === (int) $pocuserid);
    }
}

if (!$canmanage) {
    $error = 'nopermission';
} else if (!is_array($data)) {
    $error = 'invaliddata';
} else {
    $schoolids = array_values(array_filter(array_map('intval', $data)));
    try {
        $transaction = $DB->start_delegated_transaction();
        if ($action === 'assign') {
            if (empty($schoolids)) {
                throw new moodle_exception('invaliddata', 'error');
            }
            $schoolid = end($schoolids);
            if (!$DB->record_exists('course_categories', ['id' => $schoolid])) {
                throw new moodle_exception('invalidrecord', 'error');
            }
            if (!is_siteadmin() && !$DB->record_exists('schoolassign', ['userid' => $pocuserid, 'schoolid' => $schoolid])) {
                throw new moodle_exception('nopermissions', 'error', '', 'Assign School');
            }

            $updatetrainer = new stdClass();
            $updatetrainer->id = $trainer->id;
            $updatetrainer->schoolid = $schoolid;
            $success = $DB->update_record('trainer', $updatetrainer);
        } else if ($action === 'remove') {
            if (empty($schoolids)) {
                throw new moodle_exception('invaliddata', 'error');
            }
            $schoolid = reset($schoolids);
            if ((int) $trainer->schoolid !== $schoolid) {
                throw new moodle_exception('invaliddata', 'error');
            }

            $updatetrainer = new stdClass();
            $updatetrainer->id = $trainer->id;
            $updatetrainer->schoolid = null;
            $success = $DB->update_record('trainer', $updatetrainer);
        } else {
            throw new moodle_exception('invaliddata', 'error');
        }

        $transaction->allow_commit();
        if (!$success) {
            $error = 'updatefailed';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        if (!empty($transaction) && !$transaction->is_disposed()) {
            try {
                $transaction->rollback($e);
            } catch (Throwable $rollbacke) {
                // rollback() rethrows by design; keep returning the JSON error response.
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => $success ? 'success' : 'failed',
    'error' => $success ? '' : $error,
]);
