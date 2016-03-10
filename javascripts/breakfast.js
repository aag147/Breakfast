//************************//
//****** FUNCTIONS *******//
//************************//
	function shiftLogin(){
		if(	$('#adminShiftLink' ).hasClass( "register" ) ){ currentClass = "register";}
		else{ currentClass = "logIn";}
		$.ajax({
			url: '../loaded/showLogIn.php',
			type: 'POST',
			data: {currentClass:currentClass},
			success: function(data) {
				$("#adminAllContent").html(data);
			}
		});
	}
	function accountManagement(formData, type){
		formData.append("type", type);
		
		$.ajax({
			url: '../loaded/accountManagement.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(data) {
				if(data[0]==1){
					window.location.href = "../views/project.php";
				}else{
					alert("error: "+data);
				}
			}
		});
	}



//************************//
//******** EVENTS ********//
//************************//
$(document).ready(function() {
	/* PHONE MENU */
	$('#navigation').navobile({
		cta: "#show-navobile",
		changeDOM: true,
		bindSwipe: true,
		bindDrag: true,
		openOffetLeft:'70%'
		
	});
	

});	

$(document).ajaxStop(function () {
	/* FRONTPAGE ADMIN SHIFT */
	$('#adminShiftLink').click(function(event){
		shiftLogin();	
	});
	
	// LOG IN */
	$('#logInForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManagement(formData, "login");
		event.preventDefault();
	})
	// Register */
	$('#registerForm').submit(function(event) {
		var id = event.target.id;
		var formData = new FormData(document.getElementById(id));
		accountManagement(formData, "register");
		event.preventDefault();
	})
});