

//purge cache for changes to take effect

function test(M, branch_value, dept_select, deptid) {
	
	$.ajax({

		url :"./fetch.php",
		data:{cat_id:branch_value, selected:deptid}, 
		
		beforeSend : function (){
			$('.loader2').show();
			$('#'+dept_select).attr("disabled","disabled");
		},

		complete : function($response, $status){

			if ($status != "error" && $status != "timeout") {
				$('#'+dept_select).html($response.responseText);
				$('#'+dept_select).removeAttr('disabled');
				$('.loader2').hide();
			}

		},

		error : function ($responseObj){
			alert("Something went wrong while processing your request.\n\nError => "
			+ $responseObj.responseText);
		}

	}); 

}
	

function apply(M,userid, deptid, branch_select, dept_select){
	
	var currentval=0;
	var branch_value=0;
	
	if(userid>0) {

		branch_value=$('#' + branch_select).val();
		
		if(branch_value!='') {
			test(M,branch_value,dept_select, deptid);
		}
		
					
	}
	

	$('#' + branch_select).change(function(M) {
		
		branch_value=$('#' + branch_select).val();

		if(branch_value!='') {
			test(M,branch_value,dept_select, deptid);
		} else {
			$('#'+dept_select).html('<option value="">Select department</option>');
		}
			
			
	});	
			
}