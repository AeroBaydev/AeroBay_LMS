// File: local/coursecopy/amd/src/copy_course.js

define(['core/ajax'], function(ajax) {
    return {
        init: function() {
            function fetchSubcategories(categoryId, targetDropdownId) {
                if (categoryId) {
                    ajax.call([{
                        methodname: 'local_coursecopy_get_subcategories',
                        args: { categoryid: categoryId }
                    }]).done(function(data) {
                        let dropdown = document.getElementById(targetDropdownId);
                        dropdown.innerHTML = '<option value="">Select Subcategory</option>';
                        data.forEach(function(subcategory) {
                            let option = document.createElement('option');
                            option.value = subcategory.id;
                            option.textContent = subcategory.name;
                            dropdown.appendChild(option);
                        });
                    });
                }
            }

            function fetchCourses(categoryId, targetDropdownId) {
                if (categoryId) {
                    ajax.call([{
                        methodname: 'local_coursecopy_get_courses',
                        args: { categoryid: categoryId }
                    }]).done(function(data) {
                        let dropdown = document.getElementById(targetDropdownId);
                        dropdown.innerHTML = '<option value="">Select Course</option>';
                        data.forEach(function(course) {
                            let option = document.createElement('option');
                            option.value = course.id;
                            option.textContent = course.fullname;
                            dropdown.appendChild(option);
                        });
                    });
                }
            }

            document.getElementById('sourceCategory').addEventListener('change', function() {
                var categoryId = this.value;
                var sourceSubcategoryDropdown = document.getElementById('sourceSubcategory');
                if (categoryId !== '') {
                    fetchSubcategories(categoryId, 'sourceSubcategory');
                } else {
                    sourceSubcategoryDropdown.innerHTML = '<option value="">Select Subcategory</option>';
                    document.getElementById('courseid').innerHTML = '<option value="">Select Course</option>';
                }
            });

            document.getElementById('sourceSubcategory').addEventListener('change', function() {
                var categoryId = this.value;
                if (categoryId !== '') {
                    fetchCourses(categoryId, 'courseid');
                } else {
                    document.getElementById('courseid').innerHTML = '<option value="">Select Course</option>';
                }
            });

            document.getElementById('destCategory').addEventListener('change', function() {
                var categoryId = this.value;
                var destSubcategoryDropdown = document.getElementById('destSubcategory');
                if (categoryId !== '') {
                    fetchSubcategories(categoryId, 'destSubcategory');
                } else {
                    destSubcategoryDropdown.innerHTML = '<option value="">Select Subcategory</option>';
                }
            });
        }
    };
});
