//************************//
//****** FUNCTIONS *******//
//************************//

	// Show error message
	function showMessage(id, message, remove, count){		
		remove = remove || true;
		count = count || 5000;
		
		$('#'+id).html(message);
		if(remove){
			setTimeout(function (){
				$('#'+id).html('');
			}, count);
		}
	}



	// Build breakfast planner
	function buildBreakfastPlan(){
		 $.ajax({
			   type: "POST",
			   url: '../loaded/buildBreakfastPlan.php',
			   success: function(retval) {
				  $("#breakfastPlan").html(retval);
			   }
		  });
	}
	
	// Show products
	function showContent(elementtype, visuals, db_filter){
		visuals = visuals || "full";
		db_filter = db_filter || "merge";
		
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);
		 $.ajax({
			   type: "POST",
			   url: '../loaded/showAll'+elementtypeUCF+'.php',
			   data: {visuals: visuals, db_filter: db_filter},
			   success: function(retval) {
				  $("#showAll"+elementtypeUCF).html(retval);
			   }
		  });
	}
	
	// Send notifications
	function sendNotifications(formData, elementtype){
		formData.append("type", elementtype);
		
		if(elementtype == "forgotten"){
			$('#forgottenErrmsg').html("<p class='neutral'>Emailen sendes... Det kan tage et øjeblik.</p>");
			$('#forgottenView input[type=submit]').prop('disabled', true);
		}
		
		 $.ajax({
			   type: "POST",
			   url: '../loaded/sendNotifications.php',
			   data: formData,
			   processData: false,
			   contentType: false,
			   dataType: 'json',
			   success: function(retval) {
				   showMessage(elementtype+"Errmsg", retval[1], false);
				   if(retval[0]==1){
					   if(elementtype=="forgotten"){
							$("#forgottenForm span#emailSpan").addClass('hide');
							$("#forgottenForm span:not(#emailSpan)").removeClass('hide');
							var message = "Indtast den tilsendte sikkerhedskode samt et nyt kodeord til projektet. " +
									      "Det er dette kodeord du skal logge ind med fremover.<br>" +
										  "<a href='javascript:;' class='sendForgottenEmailAgain'>Klik her for at få tilsendt en ny email.</a><br>" +
										  "<a href='javascript:;' data-id='forgotten' class='adminShiftLinkDynamic'>Klik her for at prøve med en anden email.</a>";
							$("#forgottenView #pageSubTitle").html(message);
							$('#forgottenErrmsg').html('');
							$('#forgottenForm input[type=submit]').val('Log ind');
					   }
					}
					if(elementtype == "forgotten"){
						$('#forgottenForm input[type=submit]').prop('disabled', false);
					}
			   }
		  });
	}
	
	// Account management
	function manageAccount(formData, elementtype){
		formData.append("type", elementtype);
		$('#'+elementtype+'Form input[type=submit]').prop('disabled', true);
		$.ajax({
			url: '../loaded/manageAccount.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(retval) {
				showMessage(elementtype+"Errmsg", retval[1], false);
				if(retval[0]==1){
					if(elementtype=="forgotten"){
						formData.append("project_id", retval[2]);
						manageAccount(formData, "password");
					}else if(elementtype=="password"){
						manageAccount(formData, "login");
					}else if(elementtype=="login" || elementtype=="logout"){
						window.location.href = "../views/index.php";
					}else if(elementtype=='weekdays'){
						sendNotifications(new FormData(), elementtype);
					}
				}
				if(retval[0]!=1 || elementtype=="login" || elementtype=="logout" || elementtype=="weekdays"){
					$('#'+elementtype+'Form input[type=submit]').prop('disabled', false);
				}
			}
		});
	}
	
	// Add something
	function addElement(formData, elementtype){
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);
		formData.append("type", "new");
		$('#new'+elementtypeUCF+'Form input[type=submit]').prop('disabled', true);
	
		$.ajax({
			url: '../loaded/manage'+elementtypeUCF+'.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(retval) {
				showMessage('newErrmsg', retval[1]);
				if(retval[0]==1){
					if(elementtype=="account"){
						manageAccount(formData, "login");
					}else{
						showContent(elementtype);
						$(':input','#new'+elementtypeUCF+'Form').not(':button, :submit, :reset, :hidden').val('');
						$(":input#name").focus();
					}
				}
				$('#new'+elementtypeUCF+'Form input[type=submit]').prop('disabled', false);
			}
		});
	}
	

	// Change status of something
	function changeStatus(checked, id, elementtype, remove){
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);		
		var formData = new FormData();
		formData.append("value", checked);
		formData.append("type", "changeStatus");
		formData.append(elementtype+"_id", id);
		$.ajax({
			url: '../loaded/manage'+elementtypeUCF+'.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(retval) {
				if(retval[0]==1 && elementtype=='product' && remove){
					var row = document.getElementById(elementtype+"_"+id);
					if(row){row.parentNode.removeChild(row);}
					$count = $("span#totalAmount");
					$count.text(parseInt($count.text()) - 1);
					if($count.text()==0){showContent(elementtype, visuals = 'simple', db_filter = 'buy');}
					
				}else if(retval[0]==1 && elementtype=='product'){
					showMessage(id+"Checked", "<span class='saveicon'></span>", true, 500);
				}else if(retval[0]==1 && elementtype=="participant"){
					var breakfast = $("#"+id).data('breakfast_id');
					$count = $("#participants_"+breakfast+" span.participantsCount");
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
			success: function(retval) {
				if(retval[0]==1){
					var previous_chef_id = retval[3];
					var next_chef_id = retval[4];
					var next_chef_name = retval[2];
					if(chef==-1){next_chef_name = "Limbo";}
					
					// Old chef cant come
					var old_chef_check = $("#participants_"+breakfast+" li#participant_"+previous_chef_id+" input");
					if(old_chef_check.is(":checked") && chef != 0){
						changeStatus(false, old_chef_check.attr('id'), "participant", false);
						old_chef_check.attr('checked', false);
					}
					
					// New chef can come
					var new_chef_check = $("#participants_"+breakfast+" li#participant_"+next_chef_id+" input");
					if(!new_chef_check.is(":checked") && chef != -1){
						changeStatus(true, new_chef_check.attr('id'), "participant", false);
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
	function deleteElement(id, elementtype){
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);		
		$.ajax({
			url: '../loaded/manage'+elementtypeUCF+'.php',
			type: 'POST',
			data: {id: id, type: 'delete'},
			dataType: 'json',
			success: function(retval) {
				if(retval[0]==1){
					if(elementtype=="account"){
						manageAccount(new FormData(), "logout");
					}else{
						var row = document.getElementById(elementtype+"_"+id);
						if(row){row.parentNode.removeChild(row);}
						$count = $("#totalAmount");
						$count.text(parseInt($count.text()) - 1);
						if($count.text()==0){showContent(elementtype);}
					}
				}
			}
		});
	}
	
	/********* Edit something (in-line) ********/
	// Returns the inputs to span
	function backToSpan($inputs, $span, $options, elementtype, id){
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);
		
		// Changing input to span
		$inputs.replaceWith($span);
		$options.children("a.save"+elementtypeUCF).addClass('hide');
		$options.children("a.annul"+elementtypeUCF).addClass('hide');
		$("a.edit"+elementtypeUCF).removeClass('hide');
		$("a.delete"+elementtypeUCF).removeClass('hide');
		$("a.logOut").removeClass('hide');
		$("a.toggleHelp").removeClass('hide');
		
		// Remove event handlers
		$options.children("a.save"+elementtypeUCF).off("click");
		$options.children("a.annul"+elementtypeUCF).off("click");
		$('.editInput').off("keypress");
		
		// Clear error message
		$("#"+id+"Errmsg").html("");
	}

	// Making the actual edit with ajax call
	function makeTheEdit($inputs, $span, $options, elementtype, id){
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);
		
		setTimeout(function (){
			
			// Creating formdata
			formData = new FormData();
			$inputs.children('form').children('input').each(function () {
				formData.append($(this).attr('name'), $(this).val());
			});
			formData.append(elementtype+"_id", id);
			formData.append("type", "edit");
			
			// Ajax to insert new element
			$.ajax({
				url: '../loaded/manage'+elementtypeUCF+'.php',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: "json",
				success: function(retval) {
					if(retval[0] == 1){
						$inputs.children('form').children('input').each(function () {
							value = $(this).val();
							if($(this).attr('name')=="email"){value = "Email: " + value;}
							$span.children('.'+$(this).attr('name')).text(value);
						});	
						
						// Return to span
						backToSpan($inputs, $span, $options, elementtype, id);
					}else{
						showMessage(id+"Errmsg", retval[1]);
					}
				}
			});	
		}, 100);		
	}
	
		
	// Main function for inline edit
	function editInLine(id, elementtype) {
		var elementtypeUCF = elementtype[0].toUpperCase() + elementtype.substring(1);
		
		var $span = $('#'+elementtype+'_'+id+' span.span2input');
		var $options = $('#'+elementtype+'_'+id+' span.edit');
		if(elementtype=="account"){
			$span = $('span.span2input');
			$options = $('span.options');
		}
		
		// Creating new span for inputs
		$inputs = $('<span />', {
			'class': 'span2input' 
		});
		$form = $('<form />', {
			'id': 'span2inputForm' 
		});

		// Appending inputs to new span
		$span.children('span').each(function () {
			var value = $(this).text();
			var type = 'text';
			if($(this).attr('class')=="email"){
				value = value.replace("Email: ", "");
				type = 'email';
			}
			
			$input = $('<input />', {
				'type':  type,
				'id': $(this).attr('id'),
				'value': value,
				'name': $(this).attr('class'),
				'class': 'editInput'
			});			
			$form.append( $input );
		});
		$submit = $('<input />', {
				'type':  'submit',
				'class': 'hide'
		});	
		$form.append( $submit );
		$inputs.append( $form );
		
		// Visual change of span to input
		$span.replaceWith($inputs);
		
		$("a.edit"+elementtypeUCF).addClass('hide');
		$("a.delete"+elementtypeUCF).addClass('hide');
		$("a.logOut").addClass('hide');
		$("a.toggleHelp").addClass('hide');
		$options.children("a.save"+elementtypeUCF).removeClass('hide');
		$options.children("a.annul"+elementtypeUCF).removeClass('hide');
		$inputs.children("input:first").focus();

		// Save input and return to span
		$options.children("a.save"+elementtypeUCF).on("click", function(event) {
			// Click on save
			$('#span2inputForm').submit();
		});
		$('#span2inputForm').submit(function(event) {
			// Press enter
			makeTheEdit($inputs, $span, $options, elementtype, id);
			event.preventDefault();
		});
		
		// Annul input and return to span
		$options.children("a.annul"+elementtypeUCF).one("click", function(event){
			// Return to span
			backToSpan($inputs, $span, $options, elementtype, id);
		});
	};
	
	
	
	/********* NO AJAX FUNCTIONS - ONLY VISUAL CHANGES ********/		
	// Toggle between hide and show of participants window
	function toggleParticipantsWindow(id) {
		if($('#participants_'+id).hasClass('hide')){
			$('#participants_'+id).removeClass('hide');
            $('#breakfast_'+id).addClass('open');
		}else{
			$('#participants_'+id).addClass("hide");
            $('#breakfast_'+id).removeClass('open');
		}
		

	}
	
	// Close all participants windows
	function closeAllParticipantsWindows() {
		$("#breakfastPlan_inner li.participants").addClass('hide');
		$("#breakfastPlan_inner li.weekday").removeClass('open');
	}

	// Toggle between the index views
	function toggleFrontpageView(view, elementtype) {
		elementtype = elementtype || "static";
		
		if(elementtype == 'dynamic' && view == 'register'){
			var name = $('#loginView input#name').val();
			$('#registerView input#name').val(name);
		}else if(elementtype == 'dynamic' && view == 'forgotten'){
			$(':input','#forgottenForm').not('#name, :button, :submit, :reset, :hidden').val('');
			$("#forgottenForm span#emailSpan").removeClass('hide');
			$("#forgottenForm span#securitySpan, #forgottenForm span#passwordSpan").addClass('hide');
			$('#pageSubTitle').html('Indtast projektnavn og en email tilknyttet en af deltagerne.');
			$('#forgottenErrmsg').html('');
			$('#forgottenForm input[type=submit]').val('Send email');
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
