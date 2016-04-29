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
	
	// Show products
	function showContent(type){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		 $.ajax({
			   type: "POST",
			   url: '../loaded/showAll'+typeUCF+'.php',
			   success: function(data) {
				  $("#showAll"+typeUCF).html(data);
			   }
		  });
	}
	
	// Send notifications
	function sendNotifications(type){
		 $.ajax({
			   type: "POST",
			   url: '../loaded/sendNotifications.php',
			   data: {type: type},
			   dataType: 'json',
			   success: function(data) {
			   }
		  });
	}
	
	// Account management
	function accountManager(formData, type){
		formData.append("type", type);
		$.ajax({
			url: '../loaded/accountManager.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				if(type=='weekdays'){
					sendNotifications(type);
				}
				$("#"+type+"Errmsg").html(data[1]);
				if(data[0]==1){
					if(type!="weekdays"){window.location.href = "../views/index.php";}
				}
			}
		});
	}
	
	// Add something
	function addElement(formData, type){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		formData.append("type", "new");
	
		$.ajax({
			url: '../loaded/'+type+'Manager.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				$("#newErrmsg").html(data[1]);
				if(data[0]==1){
					showContent(type);
					$(':input','#new'+typeUCF+'Form').not(':button, :submit, :reset, :hidden').val('');
				}
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
			url: '../loaded/'+type+'Manager.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				if(data[0]==1 && remove){
					var row = document.getElementById(type+"_"+id);
					if(row){row.parentNode.removeChild(row);}
				}else if(data[0]==1 && type=="participant"){
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
					$count = $("#totalAmount");
					$count.text(parseInt($count.text()) - 1);
					if($count.text()==0){showContent(type);}
				}
			}
		});
	}
	
	/********* Edit something (in-line) ********/
	// Making the actual edit with ajax call
	function makeTheEdit($inputs, $span, $options, type, id){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		
		setTimeout(function (){
			
			// Creating formdata
			formData = new FormData();
			$inputs.children('input').each(function () {
				formData.append($(this).attr('name'), $(this).val());
			});
			formData.append(type+"_id", id);
			formData.append("type", "edit");
			
			// Ajax to insert new element
			$.ajax({
				url: '../loaded/'+type+'Manager.php',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: "json",
				success: function(data) {
					if(data[0] == 1){
						$inputs.children('input').each(function () {
							value = $(this).val();
							if($(this).attr('name')=="email"){value = "Email: " + value;}
							$span.children('.'+$(this).attr('name')).text(value);
						});	
						
						// Return to span
						backToSpan($inputs, $span, $options, type, id);
					}else{
						$("#"+id+"Errmsg").html(data[1]);
					}
				}
			});	
		}, 100);		
	}
	
	// Returns the inputs to span
	function backToSpan($inputs, $span, $options, type, id){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		
		// Changing input to span
		$inputs.replaceWith($span);
		$("a.edit"+typeUCF).removeClass('hide');
		$options.children("a.save"+typeUCF).addClass('hide');
		$options.children("a.annul"+typeUCF).addClass('hide');
		
		// Remove event handlers
		$options.children("a.save"+typeUCF).off("click");
		$options.children("a.annul"+typeUCF).off("click");
		$('.editInput').off("keypress");
		
		// Clear error message
		$("#"+id+"Errmsg").html("");
	}
		
	// Main function for inline edit
	function editInLine(id, type) {
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		
		var $span = $('#'+type+'_'+id+' span.span2input');
		var $options = $('#'+type+'_'+id+' span.edit');
		if(type=="account"){
			$span = $('span.span2input');
			$options = $('span.options');
		}
		
		// Creating new span for inputs
		$inputs = $('<span />', {
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
				'name': $(this).attr('class'),
				'class': 'editInput'
			});			
			$inputs.append( input );
		});
		
		// Visual change of span to input
		$span.replaceWith($inputs);
		
		$("a.edit"+typeUCF).addClass('hide');
		$options.children("a.save"+typeUCF).removeClass('hide');
		$options.children("a.annul"+typeUCF).removeClass('hide');
		$inputs.children("input:first").focus();
		
		// Save input and return to span
		$options.children("a.save"+typeUCF).on("click", function(event) {
			makeTheEdit($inputs, $span, $options, type, id);
		});
		$('.editInput').keypress(function(event) {
			if (event.keyCode == 13) {
				makeTheEdit($inputs, $span, $options, type, id);
			}
		});
		
		// Annul input and return to span
		$options.children("a.annul"+typeUCF).one("click", function(event){
			// Return to span
			backToSpan($inputs, $span, $options, type, id);
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
		accountManager(formData, "weekdays");
		event.preventDefault();
	})
	// Log in
	$('#logInForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManager(formData, "login");
		event.preventDefault();
	})
	// Register
	$('#registerForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManager(formData, "register");
		event.preventDefault();
	})
	
	
	/***** ADMIN LINK CLICKS *****/
	/* Edit project */
	$('.editAccount').click(function(event){
		editInLine('', "account");	
	});
	/* Delete project */
	$('.deleteAccount').click(function(event){
		if(confirm("Er du sikker på, at du vil slette hele projektet?")){
			accountManager(new FormData(), "delete");	
		}
	});
	/* Log out */
	$('.logOut').click(function(event){
		accountManager(new FormData(), "logout");	
	});
	/* Toggle login and register view */
	$('.adminShiftLink').click(function(event){
		toggleTwo('#logInView', '#registerView');
	});
	
	
	/***** PLAN *****/
	/* Edit product status and remove span */
	$(':checkbox.removeProductStatus').off('change').on('change', function() {
		changeStatus(this.checked, $(this).data('id'), "product", true);	
	});
	
});	

$(document).ajaxStop(function () {
	/***** FOR SPECIAL EVENTS WAITING FOR AJAX TO FINISH *****/
	
	/** PLAN **/
	/* Edit participant status */
	$(':checkbox.editParticipantStatus').off('change').on('change', function() {
		changeStatus(this.checked, this.id, "participant", false);
	}); 	
	// Toggle participants visibility for a weekday
	$('.showParticipants').off('click').on('click', function(event){
		toggleSingle('#participants_'+this.id);
	});		
	
	/** PRODUCTS **/
	/* Edit product */
	$('.editProduct').click(function(event){
		editInLine($(this).data('id'), "product");	
	});
	/* Delete product */
	$('.deleteProduct').off('click').on('click', function(){
		deleteElement($(this).data('id'), "product");	
	});
	/* Edit product status */
	$(':checkbox.editProductStatus').off('change').on('change', function() {
		changeStatus(this.checked, $(this).data('id'), "product", false);			
	}); 
	
	/** PARTICIPANTS **/
	/* Edit participant */
	$('.editParticipant').click(function(event){
		editInLine($(this).data('id'), "participant");	
	});
	/* Delete participant */
	$('.deleteParticipant').off('click').on('click', function(){
		var name = $(this).data('name');
		if(confirm("Er du sikker på, at du vil fjerne "+name+"?")){
			deleteElement($(this).data('id'), "participant");
		}
	});
});