<?php
namespace local_mydashboard;


defined('MOODLE_INTERNAL') || die();

class observers {

    private const RESTORE_UNLOCK_STREAK = 30;
    private const RESTORE_LIMIT = 5;

	public static function f1(\core\event\dashboard_viewed $event) {
  

    global $DB,$CFG,$OUTPUT,$PAGE;
  
      //  $redirecturl = new \moodle_url('/my/index2.php');
        // $message = 'You are being redirected...';
        // $delay = 3;

        // // // Perform the redirect
        // $redirecturl="https://dev.icloudcampus.com/update/local/coursecopy/";
        //  redirect($redirecturl, $message, $delay);
        echo'<script>
        window.location.replace("'.$CFG->wwwroot.'/mydashboard");
        </script>';
// echo "<script> alert()</script>";
    // echo  $OUTPUT->render_from_template('local_mydashbord/mydashboard', $data);
  
 
	}

    public static function user_loggedin(\core\event\user_loggedin $event): void {
        self::update_student_login_streak((int) $event->userid, (int) $event->timecreated);
    }

    public static function update_student_login_streak(int $userid, ?int $timecreated = null): void {
        global $DB;

        if ($userid <= 0) {
            return;
        }

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('student') ||
                !$dbman->table_exists('local_mydashboard_streak') ||
                !$dbman->table_exists('local_mydashboard_streak_log')) {
            return;
        }

        if (!$DB->record_exists('student', ['userid' => $userid])) {
            return;
        }

        $user = \core_user::get_user($userid, 'id, timezone, deleted, suspended', IGNORE_MISSING);
        if (!$user || !empty($user->deleted) || !empty($user->suspended)) {
            return;
        }

        $timecreated = $timecreated ?? time();
        $logindate = self::get_user_login_date($user, $timecreated);

        if ($DB->record_exists('local_mydashboard_streak_log', ['userid' => $userid, 'logindate' => $logindate])) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $log = (object) [
                'userid' => $userid,
                'logindate' => $logindate,
                'timecreated' => time(),
            ];
            $DB->insert_record('local_mydashboard_streak_log', $log);

            $streak = $DB->get_record('local_mydashboard_streak', ['userid' => $userid]);
            $updated = self::calculate_streak_record($streak, $userid, $logindate);

            if (!empty($updated->id)) {
                $DB->update_record('local_mydashboard_streak', $updated);
            } else {
                $DB->insert_record('local_mydashboard_streak', $updated);
            }

            $transaction->allow_commit();
        } catch (\dml_write_exception $e) {
            self::rollback_quietly($transaction, $e);
            return;
        } catch (\Throwable $e) {
            self::rollback_quietly($transaction, $e);
            debugging('Student login streak update failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return;
        }
    }

    private static function rollback_quietly(\moodle_transaction $transaction, \Throwable $e): void {
        if ($transaction->is_disposed()) {
            return;
        }

        try {
            $transaction->rollback($e);
        } catch (\Throwable $ignored) {
            return;
        }
    }

    private static function get_user_login_date(\stdClass $user, int $timecreated): int {
        $date = new \DateTimeImmutable('@' . $timecreated);
        $date = $date->setTimezone(\core_date::get_user_timezone_object($user));

        return (int) $date->format('Ymd');
    }

    private static function calculate_streak_record($streak, int $userid, int $logindate): \stdClass {
        $now = time();

        if (!$streak) {
            return (object) [
                'userid' => $userid,
                'currentstreak' => 1,
                'longeststreak' => 1,
                'restoreused' => 0,
                'lastlogindate' => $logindate,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
        }

        $currentstreak = max(0, (int) $streak->currentstreak);
        $longeststreak = max(0, (int) $streak->longeststreak);
        $restoreused = min(self::RESTORE_LIMIT, max(0, (int) $streak->restoreused));
        $lastlogindate = (int) $streak->lastlogindate;

        if ($lastlogindate >= $logindate) {
            $streak->timemodified = $now;
            return $streak;
        }

        $gapdays = self::days_between_dates($lastlogindate, $logindate);
        if ($gapdays === 1) {
            $currentstreak++;
        } else if ($gapdays > 1) {
            $misseddays = $gapdays - 1;
            $remainingrestores = self::RESTORE_LIMIT - $restoreused;
            if ($currentstreak >= self::RESTORE_UNLOCK_STREAK && $misseddays <= $remainingrestores) {
                $restoreused += $misseddays;
                $currentstreak++;
            } else {
                if ($currentstreak >= self::RESTORE_UNLOCK_STREAK) {
                    $restoreused = min(self::RESTORE_LIMIT, $restoreused + $misseddays);
                }
                $currentstreak = 1;
            }
        } else {
            $currentstreak = 1;
        }

        $streak->currentstreak = $currentstreak;
        $streak->longeststreak = max($longeststreak, $currentstreak);
        $streak->restoreused = $restoreused;
        $streak->lastlogindate = $logindate;
        $streak->timemodified = $now;

        return $streak;
    }

    private static function days_between_dates(int $fromdate, int $todate): int {
        $from = \DateTimeImmutable::createFromFormat('!Ymd', (string) $fromdate);
        $to = \DateTimeImmutable::createFromFormat('!Ymd', (string) $todate);
        if (!$from || !$to) {
            return 0;
        }

        return (int) $from->diff($to)->format('%r%a');
    }



}
