<?php

class trainer_table extends table_sql
{
    /** @var bool Whether to show the admin relational listing columns. */
    protected $adminlisting = false;

    function __construct($uniqueid, $adminlisting = false)
    {
        parent::__construct($uniqueid);
        $this->adminlisting = $adminlisting;

        if ($this->adminlisting) {
            $columns = array('serialno', 'fullname', 'email', 'contact', 'assignedschools', 'assignedpocs', 'assignedcourses', 'edit');
            $headers = array('S.No', 'Trainer Name', 'Email', 'Mobile Number', 'Assigned School Name', 'Assigned POC Name', 'School Courses', 'Action');
        } else {
            $columns = array('serialno', 'trainderid', 'fullname', 'contact', 'assignedschools', 'assignedcourses', 'designation', 'edit');
            $headers = array('S.No', 'Trainer ID', 'Full Name', 'Contact', 'Assigned School Name', 'School Courses', 'Designation', 'Action');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
            array_pop($columns);
            array_pop($headers);
            $this->define_columns($columns);
            $this->define_headers($headers);
        }
        foreach ($columns as $column) {
            $this->no_sorting($column);
        }
    }

    function col_fullname($values) {
        $name = fullname((object) [
            'firstname' => $values->firstname ?? '',
            'lastname' => $values->lastname ?? '',
        ]);
        $url = new moodle_url('/user/profile.php', ['id' => $values->id]);

        return html_writer::link($url, s($name), [
            'class' => 'trainer-name-link text-primary',
            'title' => 'View trainer profile',
        ]);
    }

    function col_assignedschools($values) {
        $val = trim($values->assignedschools ?? '');
        return $val === '' ? html_writer::span('-', 'trainer-empty-value') : s($val);
    }

    function col_assignedpocs($values) {
        $val = trim($values->assignedpocs ?? '');
        return $val === '' ? html_writer::span('-', 'trainer-empty-value') : s($val);
    }

    function col_assignedcourses($values) {
        return $this->render_grouped_courses($values->assignedcourses ?? '');
    }

    function col_statuslabel($values) {
        $status = !empty($values->statuslabel) ? $values->statuslabel : 'Active';
        $class = core_text::strtolower($status) === 'active' ? 'trainer-status trainer-status-active' : 'trainer-status trainer-status-inactive';
        return html_writer::span(s($status), $class);
    }

    protected function render_compact_list($value, $class, $limit = 2) {
        if (empty($value)) {
            return html_writer::span('-', 'trainer-empty-value');
        }

        $items = array_values(array_filter(array_map('trim', explode(',', $value))));
        if (empty($items)) {
            return html_writer::span('-', 'trainer-empty-value');
        }

        $visibleitems = array_slice($items, 0, $limit);
        $content = '';
        foreach ($visibleitems as $item) {
            $content .= html_writer::span(s($item), 'trainer-list-pill');
        }

        $remaining = count($items) - count($visibleitems);
        if ($remaining > 0) {
            $content .= html_writer::span('+' . $remaining . ' more', 'trainer-list-more', [
                'title' => s(implode(', ', $items)),
            ]);
        }

        return html_writer::span($content, 'trainer-compact-list ' . $class);
    }

    protected function render_grouped_courses($value) {
        if (empty($value)) {
            return html_writer::span('-', 'trainer-empty-value');
        }

        $items = array_values(array_filter(array_map('trim', explode(',', $value))));
        if (empty($items)) {
            return html_writer::span('-', 'trainer-empty-value');
        }

        $levels = [];
        $othercourses = [];
        foreach ($items as $item) {
            if (preg_match('/level\s*([0-9]+).*?grade\s*([0-9]+)/i', $item, $matches)) {
                $levelnumber = (int) $matches[1];
                $grade = (int) $matches[2];
                $levels[$levelnumber][$grade] = 'Grade ' . $grade;
            } else {
                $othercourses[] = $item;
            }
        }

        $summaries = [];
        foreach ($levels as $levelnumber => $grades) {
            ksort($grades);
            $gradecount = count($grades);
            $allgrades = $this->get_available_level_grades($levelnumber);
            if ($gradecount >= 4 || (!empty($allgrades) && empty(array_diff($allgrades, array_keys($grades))))) {
                $summaries[] = 'Level ' . $levelnumber . ' • All Grades';
            } else {
                $summaries[] = 'Level ' . $levelnumber . ' • ' . implode(', ', array_values($grades));
            }
        }

        $summaries = array_merge($summaries, $othercourses);
        if (empty($summaries)) {
            return html_writer::span('-', 'trainer-empty-value');
        }

        $visibleitems = array_slice($summaries, 0, 2);
        $content = '';
        foreach ($visibleitems as $summary) {
            $content .= html_writer::span(s($summary), 'trainer-course-summary');
        }

        $remaining = count($summaries) - count($visibleitems);
        if ($remaining > 0) {
            $content .= html_writer::span('+' . $remaining . ' more', 'trainer-list-more', [
                'title' => s(implode(', ', $summaries)),
            ]);
        }

        return html_writer::span($content, 'trainer-course-list');
    }

    protected function get_available_level_grades($levelnumber) {
        global $DB;

        static $levelgrades = null;
        if ($levelgrades === null) {
            $levelgrades = [];
            $records = $DB->get_records_sql(
                "SELECT id, fullname
                   FROM {course}
                  WHERE visible = 1
                    AND id <> :siteid
                    AND LOWER(fullname) LIKE :levelpattern",
                ['siteid' => SITEID, 'levelpattern' => '%level%grade%']
            );

            foreach ($records as $record) {
                if (preg_match('/level\s*([0-9]+).*?grade\s*([0-9]+)/i', $record->fullname, $matches)) {
                    $level = (int) $matches[1];
                    $grade = (int) $matches[2];
                    $levelgrades[$level][$grade] = $grade;
                }
            }

            foreach ($levelgrades as $level => $grades) {
                ksort($grades);
                $levelgrades[$level] = array_keys($grades);
            }
        }

        return $levelgrades[$levelnumber] ?? [];
    }

    function col_edit($values)
    {
        global $CFG;
        $editurl = new moodle_url('/local/trainer/edit_trainer_form.php', ['id' => $values->id]);
        $deleteparams = ['id' => $values->id];
        if ($this->adminlisting) {
            $deleteparams['returnurl'] = (new moodle_url('/local/trainer/index.php'))->out_as_local_url(false);
        }
        $deleteurl = new moodle_url('/local/trainer/delete_trainer.php', $deleteparams);

        $button_html = '';
        if ($this->adminlisting) {
            $schoolurl = new moodle_url('/local/trainer/school.php', [
                'id' => $values->id,
                'returnurl' => (new moodle_url('/local/trainer/index.php'))->out_as_local_url(false),
            ]);
            $button_html .= html_writer::link($schoolurl, html_writer::tag('i', '', ['class' => 'fa fa-school']), [
                'class' => 'btn btn-primary mr-2',
                'title' => 'Assign School',
            ]);
            $button_html .= html_writer::link($editurl, html_writer::tag('i', '', ['class' => 'fa fa-pencil']), [
                'class' => 'btn btn-primary mr-2',
                'title' => 'Edit Trainer',
            ]);
        } else {
            $schoolurl = new moodle_url('/local/trainer/school.php', ['id' => $values->id]);
            $button_html .= html_writer::link($schoolurl, html_writer::tag('i', '', ['class' => 'fa fa-school']), [
                'class' => 'btn btn-primary mr-2',
                'title' => 'Assign School',
            ]);
            $button_html .= html_writer::link($editurl, html_writer::tag('i', '', ['class' => 'fa fa-pencil']), [
                'class' => 'btn btn-primary mr-2',
                'title' => 'Edit Trainer',
            ]);
        }
        $button_html .= html_writer::link($deleteurl, html_writer::tag('i', '', ['class' => 'fa fa-trash']), [
            'class' => 'btn btn-primary',
            'title' => 'Delete Trainer',
        ]);
        return html_writer::span($button_html, 'trainer-actions');
    }


    function define_headers($headers)
    {
        parent::define_headers($headers);
        $this->no_sorting('edit');
   
        

    }
}
