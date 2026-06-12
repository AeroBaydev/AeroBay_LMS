<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Sends a Moodle notification to targeted students when a news announcement is published.
 * 
 * @param stdClass $news The news record containing schoolid, gradeid, news (message text)
 */
function local_news_send_notifications($news) {
    global $DB, $USER, $CFG;

    // Build the query to find targeted students.
    // $news->schoolid and $news->gradeid are comma-separated strings
    $schoolids = array_filter(array_map('intval', explode(',', $news->schoolid ?? '')));
    $gradeids = array_filter(array_map('intval', explode(',', $news->gradeid ?? '')));

    error_log("NEWS id=" . ($news->id ?? 'unknown'));
    error_log("NEWS schoolid=" . ($news->schoolid ?? ''));
    error_log("NEWS gradeid=" . ($news->gradeid ?? ''));

    if (empty($schoolids) || empty($gradeids)) {
        return;
    }

    list($schoolsql, $schoolparams) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'sch');
    
    // Map the string gradeids (e.g. "8") to actual student.gradeid using course_categories.name
    $all_categories = $DB->get_records('course_categories', null, '', 'id, name');
    $mapped_gradeids = [];
    foreach ($all_categories as $cat) {
        $grade_number = preg_replace('/[^0-9]/', '', $cat->name);
        if ($grade_number !== '' && in_array((int)$grade_number, $gradeids, true)) {
            $mapped_gradeids[] = $cat->id;
        }
    }
    
    // Combine with raw gradeids in case some are stored as literal IDs
    $final_gradeids = array_unique(array_merge($mapped_gradeids, $gradeids));

    if (empty($final_gradeids)) {
        return;
    }

    list($gradesql, $gradeparams) = $DB->get_in_or_equal($final_gradeids, SQL_PARAMS_NAMED, 'grd');
    $params = array_merge($schoolparams, $gradeparams);

    // Get valid Moodle user IDs so we don't send to deleted/suspended users
    $sql = "SELECT s.userid, s.gradeid 
              FROM {student} s
              JOIN {user} u ON u.id = s.userid
             WHERE s.schoolid $schoolsql 
               AND s.gradeid $gradesql
               AND u.deleted = 0
               AND u.suspended = 0";

    $students = $DB->get_records_sql($sql, $params);
    
    $studentids = [];
    foreach ($students as $student) {
        $studentids[] = $student->userid;
        error_log("Candidate student: userid=" . $student->userid . " gradeid=" . $student->gradeid);
    }
    
    error_log("Final recipient userids: " . implode(',', $studentids));

    if (empty($studentids)) {
        return;
    }

    $sender = core_user::get_noreply_user();
    
    // Fallback if current user is admin/teacher creating this
    if (isloggedin() && !isguestuser()) {
        $sender = $USER;
    }

    foreach ($studentids as $userid) {
        $userto = core_user::get_user($userid);
        if (!$userto) {
            continue;
        }
        
        $message_html = '<strong>New Announcement</strong><br><br>' . format_text($news->news, FORMAT_HTML);

        $eventdata = new \core\message\message();
        $eventdata->courseid          = SITEID;
        $eventdata->component         = 'moodle';
        $eventdata->name              = 'coursecontentupdated';
        $eventdata->userfrom          = $sender;
        $eventdata->userto            = $userto;
        $eventdata->subject           = 'New Announcement';
        $eventdata->fullmessage       = html_to_text($message_html);
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message_html;
        $eventdata->smallmessage      = 'New Announcement: ' . substr(strip_tags($news->news), 0, 100);
        $eventdata->notification      = 1;
        $eventdata->contexturl        = $CFG->wwwroot . '/mydashboard/index.php';
        $eventdata->contexturlname    = 'View Dashboard';
        
        error_log("Sending notification to userid=" . $userto->id);
        error_log("Component=" . $eventdata->component);
        error_log("Eventtype=" . $eventdata->name);
        
        $msgid = message_send($eventdata);
        
        error_log("message_send result=" . var_export($msgid, true));
        if (!$msgid) {
            error_log("Message object dump=" . print_r($eventdata, true));
        }
    }
}
