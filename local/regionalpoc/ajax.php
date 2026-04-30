<?php

require_once('../../config.php');

// Appointment AJAX
$id = optional_param('id', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

if ($id && $userid) {

    $modal_html = "
      <div class='modal-content'>
        <table class='table'>
          <thead>
            <tr>
              <th>Serial No</th>
              <th>ARM Names</th>
              <th>Assiged Schools</th>
            </tr>
          </thead>
          <tbody>";

    $armNames = $DB->get_records_sql("SELECT ar.id as id, ar.username as name from {regionalpoc} ar join {assigned_arm} aaa on ar.id = aaa.armid where aaa.rmid = $id");
    $index = 1;
    foreach ($armNames as  $armName) {
        $schools = $DB->get_record_sql("SELECT count(schoolid) as count from {schoolassign} where userid=$armName->id");
        foreach ($schools as $sch1) {

            $modal_html .= "
            <tr>
              <td>" . ($index) . "</td>
              <td>$armName->name</td>
              <td>$sch1</td>
            </tr>";
            $index += 1;
        }
    }
    $modal_html .= "
          </tbody>
          <tfoot>
              <tr>
                <td colspan='3' style='text-align: center;'>
                  <button type='button' class='btn btn-secondary close-modal'>Close</button>
                </td>
              </tr>
            </tfoot>
        </table>
      </div>
    ";

    echo json_encode(['html' => $modal_html]);

    exit;
}


$categoryid = optional_param('categoryid', 0, PARAM_INT);
global $DB;
$empty = new stdClass();
$empty->id = 0;
$empty->name = 'Select Class';
$res = ['0' => $empty];
if ($categoryid != 0) {
    $subcategories = $DB->get_records_sql("select cc.id,cc.name from {course_categories} cc where cc.parent=$categoryid");
    foreach ($subcategories as $subcategory) {
        $obj = new stdClass();
        $obj->id = $subcategory->id;
        $obj->name = $subcategory->name;
        $res[] = $obj;
    }
    $response = ['data' => $res];
} else {
    $response = false;
}
echo json_encode($response);
exit();
