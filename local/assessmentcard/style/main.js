window.addEventListener("load", () => {
    const school = document.getElementById("id_school");
    const grade = document.getElementById("id_grade");
    const course = document.getElementById("id_course");
    const baseUrl = window.location.origin + "/" + window.location.pathname.split("/")[1];
    
    console.log("sandeep"+baseUrl)
    school.addEventListener('change', function() {
        const data = {};
        data.id = this.value;
        if(this.value==0){
          $("#id_grade").html("<option id='0'>Please select grade</option");
        }

        $.ajax({
            url: baseUrl + "/local/attendance/style/fetch_grade.php",
            method: "post",
            data,
            dataType: "json",
            async: true,
            success: function (resp) {
                console.log(resp);
                console.log(resp['html']);
                $("#id_grade").html(resp['html']);
                $("#id_course").html("<option id='0'>Please select course</option");
                
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
      if(this.value==0){
        $("#id_course").html("<option id='0'>Please select course</option");
      }
      $.ajax({
          url: baseUrl + "/local/attendance/style/fecth_course.php",
          method: "post",
          data,
          dataType: "json",
          async: true,
          success: function (resp) {
              console.log(resp);
            
              $("#id_course").html(resp['html3'])
              
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




