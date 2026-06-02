<?php
namespace local_regionalpoc;

defined('MOODLE_INTERNAL') || die();

use core\event\user_created;

class send_email {

    /**
     * User-created observer retained for compatibility.
     *
     * ARM credential email is sent explicitly from rm_arm_form.php after role
     * and school assignment are complete, so this observer must not send mail.
     *
     * @param user_created $event
     */
    public static function user_created(user_created $event): void {
        return;
    }
}
