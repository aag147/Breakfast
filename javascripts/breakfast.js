//************************//
//****** FUNCTIONS *******//
//************************//


	// Build breakfast planner
	function buildBreakfastPlan(){
		 $.ajax({
			   type: "POST",
			   url: '../loaded/buildBreakfastPlan.php',
			   success: function(data) {
				  $("#breakfastPlan").html(data);
			   }
		  });
	}
	
	// Account management
	function accountManagement(formData, type){
		formData.append("type", type);
		$.ajax({
			url: '../loaded/accountManagement.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				$("#"+type+"Errmsg").html(data);
				if(data[0]==1){
					if(type!="weekdays"){window.location.href = "../views/index.php";}
				}
			}
		});
	}
	
	// Add something
	function addElement(formData, type){
		formData.append("type", "new");
	
		$.ajax({
			url: '../loaded/'+type+'Management.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				$("#newErrmsg").html(data);
				if(data==1){location.reload();}
			}
		});
	}
	

	// Change status of something
	function changeStatus(checked, id, type, remove){
		var formData = new FormData();
		formData.append("value", checked);
		formData.append("type", "changeStatus");
		formData.append(type+"_id", id);
		$.ajax({
			url: '../loaded/'+type+'Management.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				if(data==1 && remove){
					var row = document.getElementById(type+"_"+id);
					if(row){row.parentNode.removeChild(row);}
				}else if(data==1 && type=="participant"){
					date = $("#"+id).data('id');
					$count = $("#participants_"+date+" span.participantsCount");
					if(checked){
						$count.text(parseInt($count.text()) + 1);
					}else{
						$count.text(parseInt($count.text()) - 1);
					}
				}
			}
		});
	}
	
	// Delete something
	function deleteElement(id, type){
		$.ajax({
			url: '../loaded/delete.php',
			type: 'POST',
			data: {type: type, id: id},
			dataType: 'json',
			success: function(data) {
				if(data==1){
					var row = document.getElementById(type+"_"+id);
					if(row){row.parentNode.removeChild(row);}
				}
			}
		});
	}
	
	// Edit something (in-line)
	function editInLine(id, type) {
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		
		var $span = $('#'+type+'_'+id+' span.span2input');
		var $options = $('#'+type+'_'+id+' span.options');
		if(type=="account"){
			$span = $('span.span2input');
			$options = $('span.options');
		}
		
		// Creating new span for inputs
		inputs = $('<span />', {
			'class': 'span2input' 
		});
		
		// Appending inputs to new span
		$span.children('span').each(function () {
			value = $(this).text();
			if($(this).attr('class')=="email"){value = value.replace("Email: ", "");}
			
			input = $('<input />', {
				'type':  'text',
				'id': $(this).attr('id'),
				'value': value,
				'name': $(this).attr('class')
			});			
			inputs.append( input );
		});
		
		// Visual change of span to input
		$span.replaceWith(inputs);
		
		$("a.edit"+typeUCF).addClass('hide');
		$options.children("a.save"+typeUCF).removeClass('hide');
		inputs.children("input:first").focus();
		
		// Save input and return to span
		$options.children("a.save"+typeUCF).one("click", function(event){		
			setTimeout(function (){
				
				// Creating formdata
				formData = new FormData();
				inputs.children('input').each(function () {
					formData.append($(this).attr('name'), $(this).val());
				});
				formData.append(type+"_id", id);
				formData.append("type", "edit");
				
				// Ajax to insert new element
				$.ajax({
					url: '../loaded/'+type+'Management.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					dataType: "json",
					success: function(data) {
						if(data[0] == 1){
							inputs.children('input').each(function () {
								value = $(this).val();
								if($(this).attr('name')=="email"){value = "Email: " + value;}
								$span.children('.'+$(this).attr('name')).text(value);
							});	
						}
					}
				});	
				
				// Return to span
				inputs.replaceWith($span);
				$("a.edit"+typeUCF).removeClass('hide');
				$options.children("a.save"+typeUCF).addClass('hide');
			}, 100);
		});		
	};
	
		
	// Toggle between hide and show for single element
	function toggleSingle(first) {
		if($(first).hasClass('hide')){
			$(first).removeClass('hide');
		}else{
			$(first).addClass("hide");
		}
	}

	// Toggle between showing one of two elements
	function toggleTwo(first, second) {
		if($(first).hasClass('hide')){
			$(second).addClass("hide");
			$(first).removeClass('hide');
		}else{
			$(first).addClass("hide");
			$(second).removeClass('hide');
		}
	}	


//************************//
//******** EVENTS ********//
//************************//
$(document).ready(function() {
	/***** ADMIN FORM SUBMITS *****/
	// New participant
	$('#newParticipantForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		addElement(formData, "participant");
		event.preventDefault();
	})	
	// New product
	$('#newProductForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		addElement(formData, "product");
		event.preventDefault();
	})
	// Edit breakfast weekdays
	$('#editBreakfastWeekdays').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManagement(formData, "weekdays");
		event.preventDefault();
	})
	// Log in
	$('#logInForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManagement(formData, "login");
		event.preventDefault();
	})
	// Register
	$('#registerForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManagement(formData, "register");
		event.preventDefault();
	})
	
	
	/***** ADMIN LINK CLICKS *****/
	/* Edit participant */
	$('.editParticipant').click(function(event){
		editInLine(this.id, "participant");	
	});
	/* Edit product */
	$('.editProduct').click(function(event){
		editInLine(this.id, "product");	
	});
	/* Edit project */
	$('.editAccount').click(function(event){
		editInLine(this.id, "account");	
	});
	/* Delete participant */
	$('.deleteParticipant').click(function(event){
		deleteElement(this.id, "participant");	
	});
	/* Delete product */
	$('.deleteProduct').click(function(event){
		deleteElement(this.id, "product");	
	});
	/* Delete project */
	$('.deleteAccount').click(function(event){
		if(confirm("Are you sure?")){
			accountManagement(new FormData(), "delete");	
		}
	});
	/* Log out */
	$('.logOut').click(function(event){
		accountManagement(new FormData(), "logout");	
	});
	/* Toggle login and register view */
	$('.adminShiftLink').click(function(event){
		toggleTwo('#logInView', '#registerView');
	});
	
	
	/***** ADMIN CHECK BOXES *****/
	/* Edit product status */
	$(':checkbox.editProductStatus').off('change').on('change', function() {
		changeStatus(this.checked, this.id, "product", false);			
	}); 
	/* Edit product status and remove span */
	$(':checkbox.removeProductStatus').off('change').on('change', function() {
		changeStatus(this.checked, this.id, "product", true);	
	});
	
});	

$(document).ajaxStop(function () {
	/***** FOR SPECIAL EVENTS WAITING FOR AJAX TO FINISH *****/
	/* Edit participant status */
	$(':checkbox.editParticipantStatus').off('change').on('change', function() {
		changeStatus(this.checked, this.id, "participant", false);
	}); 	
	// Toggle participants visibility for a weekday
	$('.showParticipants').click(function(event){
		toggleSingle('#participants_'+this.id);
	});		
});