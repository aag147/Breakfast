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
	function showContent(type, visuals = 'full', db_filter = 'merge'){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		 $.ajax({
			   type: "POST",
			   url: '../loaded/showAll'+typeUCF+'.php',
			   data: {visuals: visuals, db_filter: db_filter},
			   success: function(data) {
				  $("#showAll"+typeUCF).html(data);
			   }
		  });
	}
	
	// Send notifications
	function sendNotifications(formData, type){
		formData.append("type", type);
		
		if(type == "forgotten"){$('#forgottenErrmsg').html("<p class='neutral'>Emailen sendes... Det kan tage et øjeblik.</p>");}
		
		 $.ajax({
			   type: "POST",
			   url: '../loaded/sendNotifications.php',
			   data: formData,
			   processData: false,
			   contentType: false,
			   dataType: 'json',
			   success: function(data) {
				   $("#"+type+"Errmsg").html(data[1]);
				   if(data[0]==1){
					   if(type=="forgotten"){
							$("#forgottenForm span#emailSpan").addClass('hide');
							$("#forgottenForm span:not(#emailSpan)").removeClass('hide');
							var message = 'Indtast den tilsendte sikkerhedskode samt et nyt kodeord til projektet.' +
									      'Det er dette kodeord du skal logge ind med fremover.';
							$("#forgottenView #pageSubTitle").html(message);
							$('#forgottenErrmsg').html('');
					   }
					}
			   }
		  });
	}
	
	// Account management
	function accountManager(formData, type){
		formData.append("type", type);		
		$.ajax({
			url: '../loaded/manageAccount.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				$("#"+type+"Errmsg").html(data[1]);
				if(data[0]==1){				
					if(type=="login" || type=="register" || type=="logout" || type=="delete" || type=="forgotten"){
						window.location.href = "../views/index.php";
					}else if(type=='weekdays'){
						sendNotifications(new FormData(), type);
					}
				}
			}
		});
	}
	
	// Add something
	function addElement(formData, type){
		var typeUCF = type[0].toUpperCase() + type.substring(1);
		formData.append("type", "new");
	
		$.ajax({
			url: '../loaded/manage'+typeUCF+'.php',
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
					$(":input#name").focus();
				}
			}
		});
	}
	

	// Change status of something
	function changeStatus(checked, id, type, remove){
		var typeUCF = type[0].toUpperCase() + type.substring(1);		
		var formData = new FormData();
		formData.append("value", checked);
		formData.append("type", "changeStatus");
		formData.append(type+"_id", id);
		$.ajax({
			url: '../loaded/manage'+typeUCF+'.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				if(data[0]==1 && type=='product' && remove){
					var row = document.getElementById(type+"_"+id);
					if(row){row.parentNode.removeChild(row);}
					$count = $("span#totalAmount");
					$count.text(parseInt($count.text()) - 1);
					if($count.text()==0){showContent(type, 'simple', 'buy');}
					
				}else if(data[0]==1 && type=="participant"){
					var date = $("#"+id).data('id');
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
	
	// Advanced version of the above for chef changing
	function changeChef(chef, breakfast, original){
		var formData = new FormData();
		formData.append("chef_id", chef);
		formData.append("type", "changeChef");
		formData.append("breakfast_id", breakfast);
		formData.append("original_id", original);
		$.ajax({
			url: '../loaded/manageParticipant.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(data) {
				if(data[0]==1){
					var previous_chef_id = data[3];
					var next_chef_id = data[4];
					var next_chef_name = data[2];
					if(chef==-1){next_chef_name = "Limbo";}
					
					// Old chef cant come
					var old_chef_check = $("#participants_"+breakfast+" li#participant_"+previous_chef_id+" input");
					if(old_chef_check.is(":checked") && chef != 0){
						changeStatus(false, previous_chef_id, "participant", false);
						old_chef_check.attr('checked', false);
					}
					
					// New chef can come
					var new_chef_check = $("#participants_"+breakfast+" li#participant_"+next_chef_id+" input");
					if(!new_chef_check.is(":checked") && chef != -1){
						changeStatus(true, next_chef_id, "participant", false);
						new_chef_check.attr('checked', true);
					}
					
					// Change chef
					if(chef==-1){$("#breakfast_"+breakfast+" span.theChefs span.chef_"+original).addClass('limbo');}
					else{$("#breakfast_"+breakfast+" span.theChefs span.chef_"+original).removeClass('limbo');}
					
					$("#breakfast_"+breakfast+" span.theChefs span.chef_"+original).html(next_chef_name);
					$("#participants_"+breakfast+" li#participant_"+previous_chef_id).removeClass('hide');
					$("#participants_"+breakfast+" li#participant_"+next_chef_id).addClass('hide');
					
					// Enable inputs
					if(previous_chef_id != original && previous_chef_id != -1){
						var $old_change_input = 
							$("li#participants_"+breakfast+" li:not(#changeChef_"+breakfast+original+") option.option_"+previous_chef_id);	
						$old_change_input.prop('disabled', false);
					}
					// Disable inputs
					if(next_chef_id != original && next_chef_id != -1){
						var $new_change_input = 
							$("li#participants_"+breakfast+" li:not(#changeChef_"+breakfast+original+") option.option_"+next_chef_id);	
						$new_change_input.prop('disabled', true);
					}
					
				}
			}
		});
	}
	
	// Delete something
	function deleteElement(id, type){
		var typeUCF = type[0].toUpperCase() + type.substring(1);		
		$.ajax({
			url: '../loaded/manage'+typeUCF+'.php',
			type: 'POST',
			data: {id: id, type: 'delete'},
			dataType: 'json',
			success: function(data) {
				if(data[0]==1){
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
				url: '../loaded/manage'+typeUCF+'.php',
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
	function toggleParticipantsWindow(id) {
		if($('#participants_'+id).hasClass('hide')){
			$('#participants_'+id).removeClass('hide');
            $('#breakfast_'+id).addClass('open');
		}else{
			$('#participants_'+id).addClass("hide");
            $('#breakfast_'+id).removeClass('open');
		}

	}

	// Toggle between the index views
	function toggleIndexView(view, type = 'static') {
		if(type == 'dynamic' && view == 'register'){
			var name = $('#loginView input#name').val();
			$('#registerView input#name').val(name);
		}else if(type == 'dynamic' && view == 'forgotten'){
			$(':input','#forgottenForm').not('#name, :button, :submit, :reset, :hidden').val('');
			$("#forgottenForm span#emailSpan").removeClass('hide');
			$("#forgottenForm span#securitySpan, #forgottenForm span#passwordSpan").addClass('hide');
			$('#forgottenErrmsg').html('');
		}
		
		$("#adminAllContent > ul:not(#"+view+"View)").addClass("hide");
		$("#adminAllContent > ul#"+view+"View").removeClass('hide');
		$("#navigation > ul > li:not(."+view+"Menu)").removeClass("current");
		$("#navigation > ul > li."+view+"Menu").addClass("current");
	}

	// Toggle between disabled and not disabled
	function toggleDisabled(value, id) {
		if(value){
			$('#'+id+'_disabled').prop('disabled', false);
            if($('#'+id+'_disabled').val() == 0){
                $('#'+id+'_disabled').val(1);
            }
		}else{
			$('#'+id+'_disabled').prop('disabled', true);
		}
	}	

	// Toggle between all disabled and not disabled
	function toggleAllWeekdays(value) {
		$checked = $('input.weekdayChecked');
		$chefs = $('input.weekdayChefs');
		if(value){
			$checked.prop('checked', true);
			$chefs.prop('disabled', false);
            
            $chefs.each(function () {
                if($(this).val() == 0){
                    $(this).val(1);
                }
            })
		}else{
			$checked.prop('checked', false);
			$chefs.prop('disabled', true);
		}
	}

	// Toggle between all disabled and not disabled
	function toggleAllChefs(value) {
		$chefs = $('input.weekdayChefs');
		$chefs.val(value);
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
	// Forgotten password
	$('#forgottenForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		if($("#forgottenForm span#emailSpan").hasClass('hide')){
			// Final "forgotten" process creating new password
			accountManager(formData, "forgotten");
		}else{
			// Initial "forgotten" process sending emails
			sendNotifications(formData, "forgotten");
		}
		event.preventDefault();
	})
	
	
	/***** ADMIN LINK CLICKS *****/

	/** FRONT PAGE **/
	/* STATIC: Toggle login and register view */
	$('.adminShiftLink').off('click').on('click', function(){
		toggleIndexView(this.id);
	});
	
	
	/** SETTINGS **/
	/* Log out */
	$('.logOut').off('click').on('click', function(){
		accountManager(new FormData(), "logout");	
	});
	/* Edit project */
	$('.editAccount').off('click').on('click', function(){
		editInLine('', "account");	
	});
	/* Delete project */
	$('.deleteAccount').off('click').on('click', function(){
		if(confirm("Er du sikker på, at du vil slette hele projektet?")){
			accountManager(new FormData(), "delete");	
		}
	});	
});	

$(document).ajaxStop(function () {
	
	
	/***** FOR FORM SUBMITS WAITING FOR AJAX TO FINISH *****/
	// Edit breakfast weekdays
	$('#editBreakfastWeekdays').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManager(formData, "weekdays");
		event.preventDefault();
	})
	
	
	/***** FOR SPECIAL EVENTS WAITING FOR AJAX TO FINISH *****/
	
	/** FRONT PAGE **/
	/* DYNAMIC: Toggle login and register view */
	$('.adminShiftLinkDynamic').off('click').on('click', function(){
		toggleIndexView($(this).data('id'), 'dynamic');
	});
	/* DYNAMIC: Send forgotten email again */
	$('.sendForgottenEmailAgain').off('click').on('click', function(){
		var formData = new FormData(document.getElementById('forgottenForm'));
		// Initial "forgotten" process sending emails
		sendNotifications(formData, "forgotten");
	});	
	
	/** PLAN **/
	/* Edit participant status */
	$(':checkbox.editParticipantStatus').off('change').on('change', function() {
		changeStatus(this.checked, this.id, "participant", false);
	}); 	
	// Toggle participants visibility for a weekday
	$('.showParticipants').off('click').on('click', function(event){
		toggleParticipantsWindow($(this).data('id'));
	});		
	/* Edit chef */
	$('select.newChefSelect').off('change').on('change', function() {
		changeChef(this.value, $(this).data('id'), $(this).data('original'));
	});
	/* Edit product status and remove span */
	$(':checkbox.removeProductStatus').off('change').on('change', function() {
		changeStatus(this.checked, $(this).data('id'), "product", true);	
	});
	
	/** PRODUCTS **/
	/* Edit product */
	$('.editProduct').off('click').on('click', function(){
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
	$('.editParticipant').off('click').on('click', function(){
		editInLine($(this).data('id'), "participant");	
	});
	/* Delete participant */
	$('.deleteParticipant').off('click').on('click', function(){
		var name = $(this).data('name');
		if(confirm("Er du sikker på, at du vil fjerne "+name+"?")){
			deleteElement($(this).data('id'), "participant");
		}
	});
	
	/** SETTINGS **/
	/* Edit disablement for weekdays in settings */
	$(':checkbox.weekdayChecked').off('change').on('change', function() {
		toggleDisabled(this.checked, $(this).data('id'));	
	});
	/* Edit disablement and checkstatus for all weekdays in settings */
	$(':checkbox.checkAll').off('change').on('change', function() {
		toggleAllWeekdays(this.checked);	
	});	
	/* Edit chefs for all weekdays in settings */
	$('input.chefsAll').off('change').on('change', function() {
		toggleAllChefs(this.value);	
	});	
});