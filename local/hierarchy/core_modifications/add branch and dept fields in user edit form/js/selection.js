function test(M,id,outid, dept){
     $.ajax({
         url :"./fetch.php",
		 data:{cat_id:id, selected:dept}, 
         beforeSend : function (){
              //Show a message
         },
         complete : function($response, $status){
             if ($status != "error" && $status != "timeout") {
                $('#'+outid).html($response.responseText);
                $('#'+outid).attr('readonly',false);
             }
         },
         error : function ($responseObj){
             alert("Something went wrong while processing your request.\n\nError => "
                 + $responseObj.responseText);
         }
     }); 
    }
	

function apply(M,userid, dept, elem,secelem){
	var currentval=0;
	var val=0;

	$('#'+secelem).html('<option value="">Select department</option>');
	
	if(userid>0) {
		val=$('#' + elem).val();
					if(val!='') {
						test(M,val,secelem, dept);
					}
					
					
	}
			
			$('#' + elem).change(function(M){
				
					val=$('#' + elem).val();
					if(val!='') {
						test(M,val,secelem, dept);
					} else {
						$('#'+secelem).html('<option value="">Select department</option>');
					}
					
					
			});	
			
		}