window.addEventListener("load", () => {
    const school = document.getElementById("id_schoolid");
    const grade = document.getElementById("id_gradeid");
    const course = document.getElementById("id_courseid");
    const baseUrl = M.cfg.wwwroot;
    
    console.log(baseUrl)
    school.addEventListener('change', function() {
        const data = {};
        data.id = this.value;
        $.ajax({
            url: baseUrl + "/local/students/student_data.php",
            method: "post",
            data,
            dataType: "json",
            async: true,
            success: function (resp) {
                console.log(resp);
                console.log(resp['html']);
                $("#id_gradeid").html(resp['html'])
                
              if (resp.success){
                console.log("Ajax run successfully");
              }
            },
            error: function (xhr, status, error) {
              console.log("Error:", error);
            },
          });
    });

    grade.addEventListener('change', function() {
      const data = {};
      data.gradeid = this.value;
      $.ajax({
          url: baseUrl + "/local/students/fetch_course.php",
          method: "post",
          data,
          dataType: "json",
          async: true,
          success: function (resp) {
              console.log(resp);
            
              $("#id_courseid").html(resp['html3'])
              
            if (resp.success){
              console.log("Ajax run successfully");
            }
          },
          error: function (xhr, status, error) {
            console.log("Error:", error);
          },
        });
  });
    
    course.addEventListener('change', function() {
      const data = {};
      data.courseid = this.value;
      $.ajax({
          url: baseUrl + "/local/students/fetch_group.php",
          method: "post",
          data,
          dataType: "json",
          async: true,
          success: function (resp) {
              console.log(resp);
            
              $("#id_sectionid").html(resp['html2'])
              
            if (resp.success){
              console.log("Ajax run successfully");
            }
          },
          error: function (xhr, status, error) {
            console.log("Error:", error);
          },
        });
  });
});


document.addEventListener('DOMContentLoaded', function() {
    var usernameField = document.getElementById('id_username');
    var errorMessage = document.getElementById('id_error_username');
    var form = document.querySelector('form'); // Assuming there's only one form or you can use an ID to select the specific form

    usernameField.addEventListener('input', function(e) {
        if (/[A-Z]/.test(usernameField.value)) {
            errorMessage.textContent = 'Please enter lower case characters only';
            errorMessage.style.display = 'block';
        } else {
            errorMessage.style.display = 'none';
        }
    });

    form.addEventListener('submit', function(e) {
        if (/[A-Z]/.test(usernameField.value)) {
            e.preventDefault(); // Prevent form submission
            errorMessage.textContent = 'Please enter lower case characters only';
            errorMessage.style.display = 'block';
        }
    });
});


