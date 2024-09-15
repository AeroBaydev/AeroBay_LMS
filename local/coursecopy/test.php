<?php
require_once('../../config.php');
global $CFG, $DB,$USER;
if(!is_siteadmin()){
function get_category_tree_with_depth($categoryid, $depth = 0) {
    $category = core_course_category::get($categoryid);
    $tree = build_category_tree_with_depth($category, $depth);
    return $tree;
}

function build_category_tree_with_depth($category, $depth) {
    $children = $category->get_children();
    $tree = [
        'id' => $category->id,
        'name' => $category->name,
        'depth' => $depth,
        'courses' => [],
        'children' => []
    ];

    // Fetch courses in this category
    $courses = $category->get_courses();
    foreach ($courses as $course) {
        $tree['courses'][] = [
            'id' => $course->id,
            'fullname' => htmlspecialchars($course->fullname),
            'depth' => $depth + 1
        ];
    }

    foreach ($children as $child) {
        $tree['children'][] = build_category_tree_with_depth($child, $depth + 1);
    }

    return $tree;
}

function display_category_tree_with_depth($tree) {
    global $CFG;
    echo '<ul>';
    echo '<li class="form-control">' . str_repeat('&nbsp;', $tree['depth'] * 4);
    if (!empty($tree['children']) || !empty($tree['courses'])) {
        echo '<span class="toggle-icon"><i class="fas fa-angle-down"></i></span>';
    }
    echo '<a  href="' . $CFG->wwwroot . '/course/index.php?categoryid=' . $tree['id'] . '"><i class="ml-2 mr-2 fas fa-school"></i> ' . htmlspecialchars($tree['name']) . '</a>';
    if (!empty($tree['courses'])) {
        echo '<ul>';
        foreach ($tree['courses'] as $course) {
            echo '<li>' . str_repeat('&nbsp;', $course['depth'] * 4) . '<a class="form-control" href="' . $CFG->wwwroot . '/course/view.php?id=' . $course['id'] . '"><i class="ml-2 mr-2 	fas fa-graduation-cap"></i> ' . $course['fullname'] . '</a></li>';
        }
        echo '</ul>';
    } 
    if (!empty($tree['children'])) {
        echo '<ul>';
        foreach ($tree['children'] as $child) {
            display_category_tree_with_depth($child);
        }
        echo '</ul>';
    }
    echo '</li>';
    echo '</ul>';
}
$schoolids = $DB->get_records_sql("SELECT schoolid FROM {schoolassign} WHERE userid = $USER->id");
// var_dump($schoolids);die;
// $categoryid = optional_param('categoryid', 1, PARAM_INT);
foreach ($schoolids as $schoolid) {
    $categoryid=$schoolid->schoolid;
$tree = get_category_tree_with_depth($categoryid);
}
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    #category-tree ul {
        list-style-type: none;
        padding-left: 20px;
    }
    #category-tree li {
        margin: 5px 0;
    }
    #category-tree ul {
        margin: 20px 0;
    }
    #category-tree a.form-control {
        display: inline-block;
        padding: 5px;
        background-color: #f1f1f1;
        color: #333;
        border: 1px solid #ccc;
        border-radius: 4px;
        text-decoration: none;
    }
    #category-tree a.form-control:hover {
        background-color: #e2e2e2;
    }
    #category-tree li.form-control {
        display: inline-block;
        padding: 5px;
        background-color: #f1f1f1;
        color: #333;
        border: 1px solid #ccc;
        border-radius: 4px;
        text-decoration: none;
    }
    #category-tree li.form-control:hover {
        background-color: #e2e2e2;
    }
    .toggle-icon {
        cursor: pointer;
        margin-right: 5px;
    }
</style>
<div id="category-tree">
    <?php 
    
    foreach ($schoolids as $schoolid) {
        $categoryid=$schoolid->schoolid;
        $tree = get_category_tree_with_depth($categoryid);
        display_category_tree_with_depth($tree);
        
    }?>
</div>
<?php
echo $OUTPUT->footer();
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleIcons = document.querySelectorAll('#category-tree .toggle-icon');
    toggleIcons.forEach(function(icon) {
        const parentLi = icon.parentElement;
        const childUl = parentLi.querySelector('ul');
        if (childUl) {
            icon.onclick = function() {
                if (childUl.style.display === 'none' || childUl.style.display === '') {
                    childUl.style.display = 'block';
                    icon.innerHTML = '<i class="fas fa-angle-up"></i>';
                } else {
                    childUl.style.display = 'none';
                    icon.innerHTML = '<i class="fas fa-angle-down"></i>';
                }
            };
            childUl.style.display = 'none';
        }
    });
});
</script>
