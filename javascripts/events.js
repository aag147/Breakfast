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
		manageAccount(formData, "login");
		event.preventDefault();
	})
	// Register
	$('#registerForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		addElement(formData, "account");
		event.preventDefault();
	})
	// Forgotten password
	$('#forgottenForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		if($("#forgottenForm span#emailSpan").hasClass('hide')){
			// Final "forgotten" process creating new password
			manageAccount(formData, "forgotten");
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
		toggleFrontpageView(this.id);
	});
	
	
	/** SETTINGS **/
	/* Log out */
	$('.logOut').off('click').on('click', function(){
		manageAccount(new FormData(), "logout");	
	});
	/* Edit project */
	$('.editAccount').off('click').on('click', function(){
		editInLine('', "account");	
	});
	/* Delete project */
	$('.deleteAccount').off('click').on('click', function(){
		if(confirm("Er du sikker på, at du vil slette hele projektet?")){
			deleteElement('', "account");	
		}
	});	
});	

$(document).ajaxStop(function () {
	
	
	/***** FOR FORM SUBMITS WAITING FOR AJAX TO FINISH *****/
	// Edit breakfast weekdays
	$('#editBreakfastWeekdays').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		manageAccount(formData, "weekdays");
		event.preventDefault();
	})
	
	
	/***** FOR SPECIAL EVENTS WAITING FOR AJAX TO FINISH *****/
	
	/** FRONT PAGE **/
	/* DYNAMIC: Toggle login and register view */
	$('.adminShiftLinkDynamic').off('click').on('click', function(){
		toggleFrontpageView($(this).data('id'), 'dynamic');
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
		changeChef(this.value, $(this).data('breakfast_id'), $(this).data('original'));
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