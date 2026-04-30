
$(document).ready(function() {
	
    $("#id_potential_searchtext").keyup(function() {
		search('potential');
    });
	
	$("#id_existing_searchtext").keyup(function() {
		search('existing');
    });
	
	function search(param1) { //param1 will be 'potential' or 'existing' 
		
		loadStart(param1); //start loading animation when search is started 
		
		var request = $.ajax({ //get data through ajax 
			url: 'search.php',
			type: 'POST',
			data: { type: param1, deptid: dept_id, searchtext: $("#id_"+param1+"_searchtext").val() }
			
		}); 

		request.done(function(data) { //on success 
			
			if(data==0) { //no records match the search 
			
				$("#id_"+param1+"_optgroup").empty(); //empty the select 
				
				$("#id_"+param1+"_optgroup").append("<option disabled='disabled'> No courses found </option>"); //option showing no courses 
			
			} else {
				
				$("#id_"+param1+"_optgroup").empty(); //empty the select 
				
				var arrayObj = JSON.parse(data); //parse the json string into a json object 
				
				for(var i=0; i<arrayObj.length; i++) { //append the options from the array 
					$("#id_"+param1+"_optgroup").append("<option value='" + arrayObj[i].id + "'>" + arrayObj[i].space + " " + arrayObj[i].name + "</option>");
				}
				
				$("#id_"+param1+"_optgroup").append("<option disabled='disabled'>&nbsp;</option>"); //append the extra disabled option at end (not necessary) 
				
			}
			
			loadEnd(param1); //stop loading animation after data is received. Execute stopping code inside callback functions only as ajax is asynchronous. 
			
		});

		request.fail(function(xhr, textStatus) { //on failure 
			alert("Something went wrong.\n\nError => \n\n"
				 + xhr.responseText); //alert the error 
				 
			loadEnd(param1); //stop loading animation after data is received 
		});
		
	}
	
	function loadStart(param1) {
		$("#id_"+param1+"_load").show();
		$("#id_"+param1+"_select").css({"background-color":"#eee"});
	}
	
	function loadEnd(param1) {
		$("#id_"+param1+"_load").hide();
		$("#id_"+param1+"_select").css({"background-color":"#fff"});
	}
	
});
