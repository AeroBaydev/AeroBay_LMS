<?php
// File: local/coursecopy/classes/form/copy_course_form.php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class copy_course_form extends moodleform {
    public function definition() {
        global $DB, $PAGE;

        $mform = $this->_form;

        // Include jQuery from CDN
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('migrate');

        // Select course to copy
        $mform->addElement('header', 'sourcecourseheader', get_string('sourcecourse', 'local_coursecopy'));
        
        // Fetch main categories for source
        try {
            $maincategories = $DB->get_record_sql(" SELECT GROUP_CONCAT(course_cat_id) as cat FROM mdl_school");
            $sql = "SELECT * FROM {course_categories} 
        WHERE parent = :parentid 
        AND id NOT IN ($maincategories->cat)";
           $params = ['parentid' => 0];
           $maincategories = $DB->get_records_sql($sql, $params);
        
        } catch (Exception $e) {
            debugging('Error fetching main categories: ' . $e->getMessage());
            throw $e;
        }
        
        $options = ['' => get_string('selectmaincategory', 'local_coursecopy')];

        foreach ($maincategories as $category) {
            $options[$category->id] = $category->name;
        }
        $mform->addElement('select', 'sourceCategory', get_string('maincategory', 'local_coursecopy'), $options);
$mform->setType('sourceCategory', PARAM_INT);
$mform->addElement('html', '<script type="text/javascript">
   window.addEventListener("beforeunload", function (event) {
  event.stopImmediatePropagation();
});
    document.getElementById("id_sourceCategory").onchange = function() {
 
       var selectedValue = this.value;
            var currentUrl = window.location.href;
            var newUrl = new URL(currentUrl);
            newUrl.searchParams.set("categoryId", selectedValue);
            window.location.href = newUrl.toString();
    };
</script>');
$catid = $_GET['categoryId'];
if ($catid && array_key_exists($catid, $options)) {
    $mform->setDefault('sourceCategory', $catid);
}


      
if ($catid){
        $maincategories = $DB->get_records('course_categories', ['parent' => $catid]);
        foreach ($maincategories as $category) {
            $optionSubCat[$category->id] = $category->name;
        }
    }
        $mform->addElement('select', 'multiselectOptions', get_string('selectoptions', 'local_coursecopy'), $optionSubCat, [
            'multiple' => 'multiple', 
            'style' => 'width: 25%; height: 200px; padding: 10px; border-radius: 5px; border: 1px solid #ccc;'
        ]);
        $mform->disabledIf('multiselectOptions', 'sourceCategory', 'eq', '');

        $mform->addElement('html', '<script type="text/javascript">
    document.getElementById("id_multiselectOptions").onchange = function() {
        var selectedOptions = Array.from(this.selectedOptions).map(option => option.value);
        
    };
</script>');


        // Course dropdown for source initially empty, will be populated via JavaScript
        // $mform->addElement('select', 'courseid', get_string('course', 'local_coursecopy'), []);
        // $mform->disabledIf('courseid', 'sourceSubcategory', 'eq', '');
       
       
        $this->add_action_buttons(true, get_string('copycourse', 'local_coursecopy'));
   
        //CIPY TO
        // JavaScript code for handling dynamic dropdowns and adding new destination categories
        $mform->addElement('header', 'sourcecourseheader', "
Select to School");
        // Fetch main categories for source
        try {
            $maincategories = $DB->get_records('course_categories', ['parent' => 0]);
        } catch (Exception $e) {
            debugging('Error fetching main categories: ' . $e->getMessage());
            throw $e;
        }
        
        $options = ['' => get_string('selectmaincategory', 'local_coursecopy')];
        // foreach ($maincategories as $category) {
        //     $options[$category->id] = $category->name;
        // }
//         $mform->addElement('select', 'sourceCategory', get_string('maincategory', 'local_coursecopy'), $options);
// $mform->setType('sourceCategory', PARAM_INT);
        // $maincategories = $DB->get_records('course_categories', ['parent' => $catid]);
        // foreach ($maincategories as $category) {
        //     $optionSubCat[$category->id] = $category->name;
        // }
    
        $mform->addElement('select', 'listing', get_string('selectoptions', 'local_coursecopy'), [], [
            'multiple' => 'multiple', 
            'style' => 'width: 25%; height: 200px; padding: 10px; border-radius: 5px; border: 1px solid #ccc;'
        ]);
        // $mform->disabled('multiselectOptions', 'sourceCategory', 'eq', '');
    
    }

    // Custom validation
    function validation($data, $files) {
        return [];
    }

    private function addDestinationCategoryElements($mform, $destOptions) {
        $mform->addElement('html', '<div class="destination-category-group d-flex" id="destination-category-group-0">');
        $mform->addElement('select', 'destCategory[]', get_string('maincategory', 'local_coursecopy'), $destOptions, [
            'id' => 'id_destCategory0'
        ]);
        $mform->addElement('select', 'destSubcategory[]', get_string('subcategory', 'local_coursecopy'), [], [
            'id' => 'id_destSubcategory0'
        ]);
        $mform->disabledIf('destSubcategory[]', 'destCategory[]', 'eq', '');
        $mform->addElement('html', '</div>');
    }

    private function getOptionsHtml($options) {
        $html = '';
        foreach ($options as $id => $name) {
            $html .= '<option value="' . $id . '">' . $name . '</option>';
        }
        return $html;
    }
}
?>
<style>
.destination-category-group .form-group.row.fitem {
    width: 40%;
}
</style>